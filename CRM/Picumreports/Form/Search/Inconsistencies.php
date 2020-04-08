<?php


error_reporting(E_ALL);
ini_set('display_errors', 1);




class CRM_Picumreports_Form_Search_Inconsistencies extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {
  private $helper;

  public function __construct(&$formValues) {
    parent::__construct($formValues);

    $this->helper = new CRM_Picumreports_InconsistenciesHelper();
  }

  function buildForm(&$form) {
    CRM_Utils_System::setTitle('PICUM Database Inconsistencies');

    $form->addRadio('queryFilter', 'Check:', $this->helper->queriesRadioButtons, ['default' => 1], '<br>', TRUE);

    // see if there's a default to set
    $defaultQueryID = CRM_Utils_Request::retrieve('qid', 'Integer');
    if ($defaultQueryID === NULL) {
      $defaultQueryID = 0;
    }

    $form->assign('elements', ['queryFilter']);
  }

  function &columns() {
    // return by reference
    $columns = array(
      'Contact Id' => 'contact_id',
      'contact' => 'sort_name',
      'Display Name' => 'display_name',
    );
    return $columns;
  }

  function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $justIDs = FALSE) {
    $sql = $this->sql($this->select(), $offset, $rowcount, $sort, $includeContactIDs, NULL);
    //die($sql);
    return $sql;
  }

  function select() {
    $select = "
      contact_a.id as contact_id
      , contact_a.sort_name
      , contact_a.display_name as display_name
    ";

    return $select;
  }

  function from() {
    $values = $this->_formValues;
    if (array_key_exists('queryFilter', $values)) {
      $from = 'FROM ' . $this->helper->queries[$values['queryFilter']]->from;
    }
    else {
      $from = "FROM civicrm_contact contact_a";
    }

    return $from;
  }

  function where($includeContactIDs = FALSE) {
    $whereParams = [];

    $values = $this->_formValues;
    if (array_key_exists('queryFilter', $values)) {
      $where = $this->helper->queries[$values['queryFilter']]->where;
    }
    else {
      $where = '1=1';
    }

    return $this->whereClause($where, $whereParams);
  }

  function templateFile() {
    return 'CRM/Contact/Form/Search/Custom.tpl';
  }
}
