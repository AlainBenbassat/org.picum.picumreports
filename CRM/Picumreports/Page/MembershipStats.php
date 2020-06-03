<?php
use CRM_Picumreports_ExtensionUtil as E;

class CRM_Picumreports_Page_MembershipStats extends CRM_Core_Page {

  public function run() {
    CRM_Utils_System::setTitle(E::ts('PICUM Membership Statistics'));

    // assign template variables
    $this->assign('noOfCurrentMembers', $this->getCurrentMembersCount());
    $this->assign('noOfCurrentCountries', $this->getCurrentCountriesCount());
    $this->assign('membersCountbyCountry', $this->getCurrentMembersCountByCountry());

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
        and m.status_id = 2
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
        and m.status_id = 2
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
        and m.status_id = 2
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
}
