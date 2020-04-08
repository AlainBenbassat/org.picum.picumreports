<?php

class CRM_Picumreports_Form_Report_InconsistenciesSummary extends CRM_Report_Form {
  private $customSearchID = 0;

  function __construct() {
    $this->_columns = array(
      'civicrm_contact' => array(
        'fields' => array(
          'column1' => array(
            'title' => 'Issue',
            'required' => TRUE,
            'dbAlias' => '1',
          ),
          'column2' => array(
            'title' => 'Number',
            'required' => TRUE,
            'dbAlias' => '1',
          ),
        ),
      ),
    );

    // get the ID of the related custom search
    try {
      $this->customSearchID = civicrm_api3('CustomSearch', 'getsingle', [
        'return' => ['value'],
        'name' => 'CRM_Picumreports_Form_Search_Inconsistencies',
      ])['value'];
    }
    catch (Exception $e) {
      // do nothing, customSearchID will be zero
    }

    parent::__construct();
  }

  function preProcess() {
    $this->assign('reportTitle', 'Database Inconsistencies');
    parent::preProcess();
  }

  function from() {
    // take small table
    $this->_from = "FROM  civicrm_domain {$this->_aliases['civicrm_contact']} ";
  }

  function selectClause(&$tableName, $tableKey, &$fieldName, &$field) {
    return parent::selectClause($tableName, $tableKey, $fieldName, $field);
  }

  public function whereClause(&$field, $op, $value, $min, $max) {
    return '';
  }

  function alterDisplay(&$rows) {
    // build the report from scratch
    $rows = [];

    $helper = new CRM_Picumreports_InconsistenciesHelper();

    foreach ($helper->queries as $q) {
      // execute the query
      $sql = "select count(*) from " . $q->from . " where " . $q->where;
      $count = CRM_Core_DAO::singleValueQuery($sql);

      // add a row
      $url = CRM_Utils_System::url('civicrm/contact/search/custom', 'reset=1&csid=' . $this->customSearchID . '&qid=' . $q->index);
      $row = [];
      $row['civicrm_contact_column1'] = $q->label;
      $row['civicrm_contact_column2'] = '<a = href="' . $url . '">' . $count . '</a>';
      $rows[] = $row;
    }

    // add link to custom search
    /*
    $url = CRM_Utils_System::url('civicrm/contact/search/custom', 'reset=1&csid=' . $this->customSearchID);
    $row['civicrm_contact_column1'] = '<a = href="' . $url . '">More details</a>';
    $row['civicrm_contact_column2'] = '';
    $rows[] = $row;
    */
  }

}
