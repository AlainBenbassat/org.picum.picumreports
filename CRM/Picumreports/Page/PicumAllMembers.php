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
    $sql = "
      select
        c.id
        , ctry.name country
        , c.organization_name
        , m.start_date
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
