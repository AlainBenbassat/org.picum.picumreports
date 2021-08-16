<?php
use CRM_Picumreports_ExtensionUtil as E;

class CRM_Picumreports_Page_PicumAllMembers extends CRM_Core_Page {
  private $MEMBERSHIP_STATUS_CURRENT = 2;
  private $MEMBERSHIP_STATUS_TERMINATED = 8;
  private $MEMBERSHIP_STATUS_WITHDRAWALS = 6;
  private $MEMBERSHIP_STATUS_NEW = 999;

  private $filterStatusId = 0;
  private $filterCountryId = 0;
  private $filterYear = 0;

  private $sortOrder;
  private $sortOrderForUrl;
  private $sortColumn;

  public function run() {
    $this->retrieveSortColumnAndOrder();
    $this->retrieveFilters();
    $this->setPageTitle();

    // create the url for the column header hyperlink
    $queryString = $this->getQueryStringForCurrentUrl();
    $currentURL = CRM_Utils_System::url('civicrm/picumallmembers', $queryString);
    $this->assign('currentURL', $currentURL);

    // filter hyperlinks
    $this->assign('membershipStatusFilterMenu', $this->getMembershipStatusFilterMenu());
    $this->assign('yearFilterMenu', $this->getYearFilterMenu());

    // retrieve the records
    $members = $this->getAllMembers();
    $this->assign('members', $members);

    parent::run();
  }

  private function getAllMembers() {
    $lastSeenOnSubquery = $this->getLastSeenOnSubquery();

    $noOfEventsThisYear = $this->getEventCountSubquery(date('Y'));
    $noOfEventsLastYear = $this->getEventCountSubquery(date('Y') - 1);

    $communicationChannelsSubquery = $this->getCommunicationChannelsSubquery();

    $codeOfConductStatusSubquery = $this->getCodeOfConductStatus();

    $contributionStatusSubquery = $this->getLastContribution();

    $filter = $this->getStatusWhereClause() . $this->getCountryWhereClause();

    $sql = "
      select
        c.id
        , ctry.name country
        , c.organization_name
        , m.start_date
        , m.end_date
        , ($lastSeenOnSubquery) last_seen_on
        , ($noOfEventsThisYear) no_of_events_this_year
        , ($noOfEventsLastYear) no_of_events_last_year
        , ($communicationChannelsSubquery) comm_channels
        , ($codeOfConductStatusSubquery) code_of_conduct
        , ($contributionStatusSubquery) contribution_status
      from
        civicrm_contact c
      inner join civicrm_membership m on
        m.contact_id = c.id
      left outer join
        civicrm_value_geographical_area_1 g on g.entity_id = c.id
      left outer join
        civicrm_country ctry on ctry.id = g.country_of_representation_1 
      where
        c.is_deleted = 0
        and c.contact_type = 'Organization'
        and m.membership_type_id = 1
        and m.owner_membership_id IS NULL
        {$filter}
      order by
        {$this->sortColumn} {$this->sortOrder}    
    ";

    $dao = CRM_Core_DAO::executeQuery($sql);
    return $dao->fetchAll();
  }

  private function getStatusWhereClause() {
    $filter = '';

    if ($this->filterStatusId == $this->MEMBERSHIP_STATUS_NEW) {
      $filter = ' and year(m.start_date) = ' . $this->filterYear;
    }
    elseif ($this->filterStatusId == $this->MEMBERSHIP_STATUS_WITHDRAWALS || $this->filterStatusId == $this->MEMBERSHIP_STATUS_TERMINATED) {
      $filter = ' and m.status_id = ' . $this->filterStatusId;
      $filter .= ' and year(m.end_date) = ' . $this->filterYear;
    }
    else {
      // current members or current members in specific year
      if ($this->filterYear == date('Y')) {
        $filter = ' and m.status_id = ' . $this->MEMBERSHIP_STATUS_CURRENT;
      }
      else {
        $filter = ' and m.status_id in (' . $this->MEMBERSHIP_STATUS_CURRENT . ',' . $this->MEMBERSHIP_STATUS_WITHDRAWALS . ',' . $this->MEMBERSHIP_STATUS_TERMINATED . ') ';
        $filter .= ' and year(m.start_date) <= ' . $this->filterYear . " and m.end_date >= '" . $this->filterYear . "-12-31'";
      }
    }

    return $filter;
  }

  private function getCountryWhereClause() {
    if ($this->filterCountryId) {
      return ' and ctry.id = ' . $this->filterCountryId;
    }
    else {
      return '';
    }
  }

  private function getLastSeenOnSubquery() {
    $sql = "
      select
        max(concat(date_format(e1.start_date, '%Y-%m-%d'), ' - ', e1.title))
      from
        civicrm_event e1
      inner join
        civicrm_participant p1 on p1.event_id = e1.id
      inner join
        civicrm_contact c1 on c1.id = p1.contact_id
      where
        c1.is_deleted = 0
      and 
        c1.employer_id = c.id 
    ";

    return $sql;
  }

  private function getEventCountSubquery($year) {
    $sql = "
      select
        count(distinct e_$year.id)
      from
        civicrm_event e_$year
      inner join
        civicrm_participant p_$year on p_$year.event_id = e_$year.id
      inner join
        civicrm_contact c_$year on c_$year.id = p_$year.contact_id
      where
        c_$year.is_deleted = 0
      and 
        c_$year.employer_id = c.id 
      and
        year(e_$year.start_date) = $year
    ";

    return $sql;
  }

  private function getCommunicationChannelsSubquery() {
    $sql = "
      select
        group_concat(distinct t.name SEPARATOR ', ')
      from
        civicrm_tag t
      inner join
        civicrm_entity_tag et on et.tag_id = t.id and et.entity_table = 'civicrm_contact'        
      inner join
        civicrm_contact employee on et.entity_id = employee.id
      where
        employee.is_deleted = 0
      and 
        employee.employer_id = c.id
      and
        t.name like 'Google%' 
      order by
        t.name        
    ";

    return $sql;
  }

  private function getCodeOfConductStatus() {
    $codeOfConductActivityId = 56;
    $activityStatusOptionGroupId = 26;

    $sql = "
      select 
        ov.label
      from 
        civicrm_activity a 
      inner join 
        civicrm_activity_contact ac on ac.activity_id = a.id
      inner join 
        civicrm_option_value ov on ov.value = a.status_id and ov.option_group_id = $activityStatusOptionGroupId
      where 
        a.activity_type_id = $codeOfConductActivityId
      and
        ac.contact_id = c.id
      order by
        activity_date_time desc 
      limit 0,1
    ";

    return $sql;
  }

  private function getLastContribution() {
    $contributionStatusOptionGroupId = 11;

    $sql = "
      select
        concat(ct.source, ': ', ovm.label)
      from
        civicrm_contribution ct
      inner join
        civicrm_option_value ovm on ovm.value = ct.contribution_status_id and ovm.option_group_id = $contributionStatusOptionGroupId
      where
        ct.contact_id = c.id
      and
        ct.source like 'Fee %'
      order by
        ct.source desc
      limit  
        0, 1
    ";

    return $sql;
  }

  private function retrieveSortColumnAndOrder() {
    $previoussortcol = CRM_Utils_Request::retrieveValue('previoussortcol', 'Integer', 2, FALSE, 'GET');
    $newsortcol = CRM_Utils_Request::retrieveValue('newsortcol', 'Integer', $previoussortcol, FALSE, 'GET');
    $sortorder = CRM_Utils_Request::retrieveValue('sortorder', 'Integer', 0, FALSE, 'GET'); // 0 = asc, 1 = desc

    if ($newsortcol == $previoussortcol) {
      // invert the sort order
      if ($sortorder == 0) {
        $sortorder = 'asc';
      }
      else {
        $sortorder = 'desc';
      }
    }
    else {
      $sortorder = 'asc';
    }

    $this->sortColumn = $newsortcol;
    $this->sortOrder = $sortorder;
    $this->sortOrderForUrl = ($sortorder == 'asc') ? 1 : 0;
  }

  private function retrieveFilters() {
    $this->retrieveFilterCountryId();
    $this->retrieveFilterStatusId();
    $this->retrieveFilterYear();
  }

  private function retrieveFilterCountryId() {
    $this->filterCountryId = CRM_Utils_Request::retrieveValue('country_id', 'Integer', 0, FALSE, 'GET');
  }

  private function retrieveFilterStatusId() {
    $this->filterStatusId = CRM_Utils_Request::retrieveValue('status_id', 'Integer', 0, FALSE, 'GET');
    if (!$this->filterStatusId) {
      $this->filterStatusId = $this->MEMBERSHIP_STATUS_CURRENT;
    }
  }

  private function retrieveFilterYear() {
    $this->filterYear = CRM_Utils_Request::retrieveValue('year', 'Integer', date('Y'), FALSE, 'GET');
  }

  private function setPageTitle() {
    if ($this->filterStatusId == $this->MEMBERSHIP_STATUS_TERMINATED) {
      $label = 'Terminated Members';
    }
    elseif ($this->filterStatusId == $this->MEMBERSHIP_STATUS_WITHDRAWALS) {
      $label = 'Withdrawn Members';
    }
    elseif ($this->filterStatusId == $this->MEMBERSHIP_STATUS_NEW) {
      $label = 'New Members';
    }
    else {
      $label = 'Current Members';
    }

    CRM_Utils_System::setTitle('PICUM ' . $label . ' ' . $this->filterYear);
  }

  private function getQueryStringForCurrentUrl($overwriteStatusId = 0, $overwriteYear = 0) {
    $queryParams = [
      'reset' => 1,
      'previoussortcol' => $this->sortColumn,
      'sortorder' => $this->sortOrderForUrl,
    ];

    if ($this->filterCountryId) {
      $queryParams['country_id'] = $this->filterCountryId;
    }

    if ($overwriteStatusId) {
      $queryParams['status_id'] = $overwriteStatusId;
    }
    elseif ($this->filterStatusId) {
      $queryParams['status_id'] = $this->filterStatusId;
    }

    if ($overwriteYear) {
      $queryParams['year'] = $overwriteYear;
    }
    else {
      $queryParams['year'] = $this->filterYear;
    }

    $queryString = '';
    foreach ($queryParams as $k => $v) {
      if ($queryString) {
        $queryString .= '&';
      }

      $queryString .= "$k=$v";
    }

    return $queryString;
  }

  private function getMembershipStatusFilterMenu() {
    $menu = '';

    $items = [
      'Current members' => $this->MEMBERSHIP_STATUS_CURRENT,
      'New Members' => $this->MEMBERSHIP_STATUS_NEW,
      'Withdrawals' => $this->MEMBERSHIP_STATUS_WITHDRAWALS,
      'Terminated' => $this->MEMBERSHIP_STATUS_TERMINATED,
    ];

    $i = 1;
    $numItems = count($items);
    foreach ($items as $item => $statusId) {
      if ($statusId == $this->filterStatusId) {
        $menu .= $item; // current status, so no hyperlink around menu item
      }
      else {
        $menu .= $this->getMenuItemWithFilterUrl($item, $statusId, 0);
      }

      $menu .= $this->addMenuSeparator($i, $numItems);

      $i++;
    }

    return $menu;
  }

  private function getYearFilterMenu() {
    $menu = '';
    $year = date('Y');

    $items = [
      $year,
      $year - 1,
      $year - 2,
    ];

    $i = 1;
    $numItems = count($items);
    foreach ($items as $year) {
      if ($year == $this->filterYear) {
        $menu .= $year; // current year, so no hyperlink around menu item
      }
      else {
        $menu .= $this->getMenuItemWithFilterUrl($year, 0, $year);
      }

      $menu .= $this->addMenuSeparator($i, $numItems);

      $i++;
    }

    return $menu;
  }

  private function getMenuItemWithFilterUrl($item, $statusId, $year) {
    $queryString = $this->getQueryStringForCurrentUrl($statusId, $year);
    $url = CRM_Utils_System::url('civicrm/picumallmembers', $queryString);

    $menuItem = "<a href=\"$url\">$item</a>";

    return $menuItem;
  }

  private function addMenuSeparator($i, $numItems) {
    if ($i != $numItems) {
      return ' | ';
    }
    else {
      return '';
    }
  }
}
