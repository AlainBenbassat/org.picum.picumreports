<?php


class PicumInconsistenciesQuery {
  public $label;
  public $index;
  public $from;
  public $where;
}

class CRM_Picumreports_InconsistenciesHelper {
  public $queries = [];
  public $queriesRadioButtons = [];

  public function __construct() {
    $this->addQueries();
  }

  function addQueries() {
    $index = 0;

    // members without treasurer
    $q = new PicumInconsistenciesQuery();
    $q->label = 'Members without treasurer';
    $q->index = $index;
    $q->from = "civicrm_contact contact_a
      inner join civicrm_membership m on m.contact_id = contact_a.id      
    ";
    $q->where = "
      contact_a.is_deleted = 0
      and contact_a.contact_type = 'Organization'
      and m.status_id = 2
      and m.membership_type_id = 1
      and m.owner_membership_id IS NULL
      and not exists (
        select 
          * 
        from 
          civicrm_entity_tag et 
        inner join 
          civicrm_tag t on et.tag_id = t.id
        inner join 
          civicrm_contact trc on trc.id = et.entity_id 
        where
          t.name  = 'Treasurer'
        and 
          trc.employer_id = contact_a.id 
      )
    ";
    $this->queries[$index] = $q;
    $this->queriesRadioButtons[$q->index] = $q->label;
    $index++;

    // members without member contacts
    $q = new PicumInconsistenciesQuery();
    $q->label = 'Members without member contacts';
    $q->index = $index;
    $q->from = "civicrm_contact contact_a
      inner join civicrm_membership m on m.contact_id = contact_a.id      
    ";
    $q->where = "
      contact_a.is_deleted = 0
      and m.status_id = 2
      and m.membership_type_id = 1
      and m.owner_membership_id IS NULL
      and not exists (
        select 
          * 
        from 
          civicrm_entity_tag et 
        inner join 
          civicrm_tag t on et.tag_id = t.id
        inner join 
          civicrm_contact trc on trc.id = et.entity_id 
        where
          t.name  = 'Member - Contact'
        and 
          trc.employer_id = contact_a.id 
      )
    ";
    $this->queries[$index] = $q;
    $this->queriesRadioButtons[$q->index] = $q->label;
    $index++;

    // employer is a member, but the contact does not have an inherited membership
    $q = new PicumInconsistenciesQuery();
    $q->label = 'Employer is a member, but employee did not inherit the membership';
    $q->index = $index;
    $q->from = "civicrm_contact contact_a
      inner join
        civicrm_relationship r on contact_a.id = r.contact_id_a and r.contact_id_b = contact_a.employer_id
      inner join
        civicrm_membership memp on contact_a.employer_id = memp.contact_id
      left outer join
        civicrm_membership mpers on contact_a.id = mpers.contact_id
    ";
    $q->where = "
      contact_a.contact_type = 'Individual'
      and mpers.id is null
      and memp.start_date <= NOW() and memp.end_date >= NOW()
      and contact_a.is_deleted = 0
    ";
    $this->queries[$index] = $q;
    $this->queriesRadioButtons[$q->index] = $q->label;
    $index++;
  }
}