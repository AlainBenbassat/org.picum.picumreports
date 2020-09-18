<?php
use CRM_Picumreports_ExtensionUtil as E;

class CRM_Picumreports_Page_EventsStats extends CRM_Core_Page {
  private $year;

  public function __construct($title = NULL, $mode = NULL) {
    // get the year from the query string
    $this->year = CRM_Utils_Request::retrieve('year', 'Integer', CRM_Core_DAO::$_nullObject, FALSE, date('Y'));

    parent::__construct($title, $mode);
  }

  public function run() {
    CRM_Utils_System::setTitle('PICUM Events in ' . $this->year);

    $returnURL = '<a href="' . CRM_Utils_System::url('civicrm/picummembersstats', "reset=1") . '">Return to PICUM CRM Statistics</a>';
    $this->assign('returnURL', $returnURL);

    $eventSummary = $this->getEventSummary();
    $this->assign('eventSummary', $eventSummary);

    $events = $this->getEvents();
    $this->assign('events', $events);

    parent::run();
  }

  private function getEventSummary() {
    // get a list of event types with count and number of particiapnts
    $sql = "
      select       
       v.label event_type,
       count(distinct e.id) total_events,
       count(p.id) total_participants
      from 
        civicrm_event e
      inner join
        civicrm_option_value v on e.event_type_id = v.value   
      inner join
        civicrm_option_group g on v.option_group_id = g.id
      left outer join
        civicrm_participant p on p.event_id = e.id and p.status_id in (1,2)
      where 
        g.name  = 'event_type'
      and
        v.is_active = 1
      AND
        e.is_active = 1
      and
        year(e.start_date) = {$this->year}    
      GROUP BY
        v.label 
      order by
        v.label    
    ";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $r = $dao->fetchAll();

    // add the totals
    if (count($r) > 0) {
      $sql = "
        select    
          count(distinct e.id) total_events,
          count(p.id) total_participants
        from 
          civicrm_event e
        left outer join
          civicrm_participant p on p.event_id = e.id and p.status_id in (1,2)
        where 
          e.is_active = 1
        and
          year(e.start_date) = {$this->year}   
      ";
      $dao = CRM_Core_DAO::executeQuery($sql);
      $dao->fetch();
      $r[] = [
        'event_type' => '<strong>TOTAL</strong>',
        'total_events' => '<strong>' . $dao->total_events . '</strong>',
        'total_participants' => '<strong>' . $dao->total_participants . '</strong>'
      ];
    }

    return $r;
  }

  private function getEvents() {
    $events = [];

    // get a list of event types
    $sql = "
      select 
       v.value 
       , v.label 
      from 
        civicrm_option_group g
      inner join
        civicrm_option_value v on v.option_group_id = g.id
      where 
        g.name  = 'event_type'
      and
        v.is_active = 1        
      order by
        v.label
    ";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      $events[$dao->label] = $this->getEventsOfType($dao->value);
    }

    return $events;
  }

  private function getEventsOfType($eventTypeId) {
    $sql = "
      select
        date_format(e.start_date, '%Y-%m-%d') start_date
        , e.title
        , count(p.id) participants
      from
        civicrm_event e 
      inner join
        civicrm_participant p on p.event_id = e.id
      where 
        p.status_id in (1, 2)
      and
        e.is_active = 1
      and
        e.event_type_id = $eventTypeId
      and
        year(e.start_date) = {$this->year}
      group by
        e.start_date, e.title
      order by
        e.start_date
    ";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $r = $dao->fetchAll();

    // add the totals
    if (count($r) > 0) {
      $sql = "
        select 
          count(p.id) 
        from
          civicrm_event e 
        inner join
          civicrm_participant p on p.event_id = e.id
        where 
          p.status_id in (1, 2)
        and
          e.event_type_id = $eventTypeId
        and
          year(e.start_date) = {$this->year} 
      ";
      $r[] = [
        'start_date' => '',
        'title' => '<strong>TOTAL</strong>',
        'participants' => '<strong>' . CRM_Core_DAO::singleValueQuery($sql) . '</strong>'
      ];
    }

    return $r;
  }

}
