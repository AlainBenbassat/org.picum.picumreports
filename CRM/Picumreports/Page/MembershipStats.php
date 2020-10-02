<?php
use CRM_Picumreports_ExtensionUtil as E;

class CRM_Picumreports_Page_MembershipStats extends CRM_Core_Page {
  private $MEMBERSHIP_STATUS_CURRENT = 2;
  private $MEMBERSHIP_STATUS_WITHDRAWLED_CANCELLED = 6;
  private $MEMBERSHIP_STATUS_TERMINATED = 8;
  private $HISTORY_NUM_YEARS = 3;

  public function run() {
    CRM_Utils_System::setTitle(E::ts('PICUM Membership Statistics'));

    // assign template variables
    $this->assign('noOfCurrentMembers', $this->getCurrentMembersCount());
    $this->assign('noOfCurrentCountries', $this->getCurrentCountriesCount());
    $this->assign('membersCountbyCountry', $this->getCurrentMembersCountByCountry());

    $membersStatusByYear = $this->getMemberhipStatusByYear();
    $this->assign('membersByYear', $membersStatusByYear);

    parent::run();
  }

  private function getCurrentMembersCount() {
    $sql = "
      select
        count(*)
      from
        civicrm_contact c
      inner join civicrm_membership m on
        m.contact_id = c.id
      where
        c.is_deleted = 0
        and c.contact_type = 'Organization'
        and m.status_id = {$this->MEMBERSHIP_STATUS_CURRENT}
        and m.membership_type_id = 1
        and m.owner_membership_id IS NULL
    ";
    $n = CRM_Core_DAO::singleValueQuery($sql);
    return $n;
  }

  private function getCurrentCountriesCount() {
    $sql = "
      select
        count(distinct g.country_of_representation_1)
      from
        civicrm_contact c
      inner join civicrm_membership m on
        m.contact_id = c.id
      left outer join
        civicrm_value_geographical_area_1 g on g.entity_id = c.id
      where
        c.is_deleted = 0
        and c.contact_type = 'Organization'
        and m.status_id = {$this->MEMBERSHIP_STATUS_CURRENT}
        and m.membership_type_id = 1
        and m.owner_membership_id IS NULL    
    ";
    $n = CRM_Core_DAO::singleValueQuery($sql);
    return $n;
  }

  private function getCurrentMembersCountByCountry() {
    $sql = "
      select
        ctry.name country
        , count(m.id) no_of_members
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
      group by
        ctry.name
      order by
        ctry.name    
    ";
    $dao = CRM_Core_DAO::executeQuery($sql);
    return $dao->fetchAll();
  }

  private function getMemberhipStatusByYear() {
    $returnArr = [];
    $currentYear = date('Y');
    for ($year = $currentYear; $year > $currentYear - $this->HISTORY_NUM_YEARS; $year--) {
      // total
      $status = [$this->MEMBERSHIP_STATUS_CURRENT, $this->MEMBERSHIP_STATUS_WITHDRAWLED_CANCELLED, $this->MEMBERSHIP_STATUS_TERMINATED];
      $condition = "year(m.start_date) <= $year and m.end_date >= '$year-12-31'";
      $total = $this->getMembersForYear($status, $condition);

      // new members
      $status = [$this->MEMBERSHIP_STATUS_CURRENT, $this->MEMBERSHIP_STATUS_WITHDRAWLED_CANCELLED, $this->MEMBERSHIP_STATUS_TERMINATED];
      $condition = "year(m.start_date) = $year";
      $new = $this->getMembersForYear($status, $condition);

      // withdrawal / cancelled
      $status = [$this->MEMBERSHIP_STATUS_WITHDRAWLED_CANCELLED];
      $condition = "year(m.end_date) = $year";
      $cancelled = $this->getMembersForYear($status, $condition);

      // terminated
      $status = [$this->MEMBERSHIP_STATUS_TERMINATED];
      $condition = "year(m.end_date) = $year";
      $terminated = $this->getMembersForYear($status, $condition);

      $returnArr[] = [$year, $total, $new, $cancelled, $terminated];
    }

    return $returnArr;
  }

  private function getMembersForYear($status, $condition) {
    $sql = "
      select
        count(m.id) no_of_members
      from
        civicrm_contact c
      inner join civicrm_membership m on
        m.contact_id = c.id
      where
        c.is_deleted = 0
        and c.contact_type = 'Organization'
        and m.status_id in (" . implode(',', $status) . ")
        and m.membership_type_id = 1
        and m.owner_membership_id IS NULL
        and $condition
    ";
    return CRM_Core_DAO::singleValueQuery($sql);
  }
}
