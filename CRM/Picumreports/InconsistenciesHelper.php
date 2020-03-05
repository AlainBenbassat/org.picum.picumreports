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

    // namen zonder hoofdletters
    $q = new PicumInconsistenciesQuery();
    $q->label = 'Members without treasurer';
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
        select * from civicrm_entity_tag et 
        inner join civicrm_tag t
        inner join civicrm_contact trc on trc.id = et.entity_id 
        where
        t.name  = 'Treasurer'
        and trc.employer_id = contact_a.id 
      )
    ";
    $this->queries[$index] = $q;
    $this->queriesRadioButtons[$q->index] = $q->label;
    $index++;
  }
}