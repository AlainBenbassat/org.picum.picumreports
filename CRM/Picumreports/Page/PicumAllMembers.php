<?php
use CRM_Picumreports_ExtensionUtil as E;

class CRM_Picumreports_Page_PicumAllMembers extends CRM_Core_Page {
  private $MEMBERSHIP_STATUS_CURRENT = 2;

  public function run() {
    CRM_Utils_System::setTitle('PICUM Current Members');

    // get the number of the column to sort on, and the order (asc/desc)
    list($newsort, $sortorder) = $this->getSort();

    // create the url for the column header hyperlink
    $invertedSortorder = ($sortorder == 'asc') ? 1 : 0;
    $currentURL = CRM_Utils_System::url('civicrm/picumallmembers', "reset=1&year=CURRENT&previoussort=$newsort&sortorder=$invertedSortorder");
    $this->assign('currentURL', $currentURL);

    $members = $this->getAllMembers($newsort, $sortorder);
    $this->assign('members', $members);

    parent::run();
  }

  private function getAllMembers($sort, $sortorder) {
    $lastSeenOnSubquery = $this->getLastSeenOnSubquery();

    $noOfEventsThisYear = $this->getEventCountSubquery(date('Y'));
    $noOfEventsLastYear = $this->getEventCountSubquery(date('Y') - 1);

    $communicationChannelsSubquery = $this->getCommunicationChannelsSubquery();

    $codeOfConductStatusSubquery = $this->getCodeOfConductStatus();

    $sql = "
      select
        c.id
        , ctry.name country
        , c.organization_name
        , m.start_date
        , ($lastSeenOnSubquery) last_seen_on
        , ($noOfEventsThisYear) no_of_events_this_year
        , ($noOfEventsLastYear) no_of_events_last_year
        , ($communicationChannelsSubquery) comm_channels
        , ($codeOfConductStatusSubquery) code_of_conduct
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
        and m.status_id = {$this->MEMBERSHIP_STATUS_CURRENT}
        and m.membership_type_id = 1
        and m.owner_membership_id IS NULL
      order by
        $sort $sortorder    
    ";

    $dao = CRM_Core_DAO::executeQuery($sql);
    return $dao->fetchAll();
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
    ";

    return $sql;
  }

  private function getSort() {
    $previoussort = CRM_Utils_Request::retrieveValue('previoussort', 'Integer', 2, FALSE, 'GET');
    $newsort = CRM_Utils_Request::retrieveValue('newsort', 'Integer', $previoussort, FALSE, 'GET');
    $sortorder = CRM_Utils_Request::retrieveValue('sortorder', 'Integer', 0, FALSE, 'GET'); // 0 = asc, 1 = desc

    if ($newsort == $previoussort) {
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

    return [$newsort, $sortorder];
  }
}
