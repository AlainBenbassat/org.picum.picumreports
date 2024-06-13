<?php
use CRM_Picumreports_ExtensionUtil as E;

class CRM_Picumreports_Form_Report_MembershipPayments extends CRM_Report_Form {
 // protected $_customGroupFilters = TRUE;
  //protected $_customGroupExtends = ['Membership'];

  function __construct() {
    $this->_columns = [
      'civicrm_contact' => [
        'dao' => 'CRM_Contact_DAO_Contact',
        'fields' => [
          'id' => [
            'title' => 'Contact ID',
            'required' => TRUE,
          ],
          'country' => [
            'title' => 'Country',
            'required' => TRUE,
            'dbAlias' => 'ctr.name',
          ],
          'organization_name' => [
            'title' => 'Donor',
            'required' => TRUE,
          ],
        ],
        'order_bys' => [
          'country' => [
            'title' => 'Country',
            'dbAlias' => 'ctr.name',
            'default' => '1',
            'default_weight' => '0',
            'default_order' => 'ASC',
          ],
          'organization_name' => [
            'title' => 'Donor',
            'default' => '1',
            'default_weight' => '1',
            'default_order' => 'ASC',
          ],
        ],
      ],
      'civicrm_membership_status' => [
        'dao' => 'CRM_Member_DAO_MembershipStatus',
        'fields' => [
          'name' => [
            'title' => 'Status',
            'default' => TRUE,
          ],
        ],
        'filters' => [
          'status_id' => [
            'name' => 'id',
            'title' => 'Membership Status',
            'type' => CRM_Utils_Type::T_INT,
            'operatorType' => CRM_Report_Form::OP_MULTISELECT,
            'options' => CRM_Member_PseudoConstant::membershipStatus(NULL, NULL, 'label'),
          ],
        ],
      ],
      'civicrm_value_membership_de_8' => [
        'fields' => [
          'comments_52' => [
            'title' => 'Comment',
            'required' => FALSE,
            'dbAlias' => 'md.comments_52'
          ],
        ],
      ],
    ];

    // add the years since 2012
    $currentYear = date('Y');
    for ($y = 2012; $y <= $currentYear; $y++) {
      $this->_columns['civicrm_years']['fields']["fee_$y"] = [
        'title' => "Fee $y",
        'default' => FALSE,
        'dbAlias' => '0.00',
        'type' => CRM_Utils_Type::T_MONEY,
      ];
      $this->_columns['civicrm_receive_dates']['fields']["receive_date_$y"] = [
        'title' => "Receive date $y",
        'default' => FALSE,
        'dbAlias' => "'-'",
        'type' => CRM_Utils_Type::T_STRING,
      ];
    }

    parent::__construct();
  }

  function preProcess() {
    $this->assign('reportTitle', 'Membership Fees');
    parent::preProcess();
  }

  function postProcess() {
    $this->beginPostProcess();

    $sql = $this->buildQuery(TRUE);

    $rows = [];
    $this->buildRows($sql, $rows);

    $this->formatDisplay($rows);
    $this->doTemplateAssignment($rows);
    $this->endPostProcess($rows);
  }

  public function select() {
    $select = $this->_columnHeaders = [];

    foreach ($this->_columns as $tableName => $table) {
      if (array_key_exists('fields', $table)) {
        foreach ($table['fields'] as $fieldName => $field) {
          if (CRM_Utils_Array::value('required', $field) ||
            CRM_Utils_Array::value($fieldName, $this->_params['fields'])
          ) {
            if ($tableName == 'civicrm_address') {
              $this->_addressField = TRUE;
            }
            elseif ($tableName == 'civicrm_email') {
              $this->_emailField = TRUE;
            }
            $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = $field['title'];
            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type'] = CRM_Utils_Array::value('type', $field);
          }
        }
      }
    }

    $this->_select = "SELECT " . implode(', ', $select);
  }

  public function from() {
    $from = "
      FROM
        civicrm_contact {$this->_aliases['civicrm_contact']}
      inner join
        civicrm_membership m on {$this->_aliases['civicrm_contact']}.id = m.contact_id
      left outer join
        civicrm_value_membership_de_8 md on m.id = md.entity_id
      left outer join
        civicrm_membership_status {$this->_aliases['civicrm_membership_status']} ON {$this->_aliases['civicrm_membership_status']}.id = m.status_id
      left outer join
        civicrm_value_geographical_area_1 g on g.entity_id = {$this->_aliases['civicrm_contact']}.id
      left outer join
        civicrm_country ctr on g.country_of_representation_1 = ctr.id
    ";

    $this->_from = $from;
  }

  public function where() {
    $this->_where = "WHERE
        m.owner_membership_id IS NULL
      and
        {$this->_aliases['civicrm_contact']}.is_deleted = 0
    ";

    // and status filter
    if (count($this->_params['status_id_value'])) {
      $statusFilter = ' and m.status_id ';
      if ($this->_params['status_id_op']) {
        $statusFilter .= ' in (';
      }
      else {
        $statusFilter .= ' not in (';
      }

      $statusFilter .= implode(', ', $this->_params['status_id_value']) . ') ';
      $this->_where .= $statusFilter;
    }
  }

  function alterDisplay(&$rows) {
    $i = 0;
    foreach ($rows as $row) {
      foreach ($row as $k => $v) {
        // check if this a year column
        if (strpos($k, 'civicrm_years_fee_') === 0) {
          // extract the year
          $year = str_replace('civicrm_years_fee_', '', $k);

          // get the contributions for that contact and year and staus = Completed or Pending
          $sql = "
            select
              total_amount,
              date_format(receive_date, '%Y-%m-%d') receive_date
            from
              civicrm_contribution
            where
              contact_id = %1
            and
              source = %2
            and
              contribution_status_id in (1, 2)
          ";
          $sqlParams = [
            1 => [$row['civicrm_contact_id'], 'Integer'],
            2 => ["Fee $year", 'String'],
          ];

          $total = null;
          $receiveDate = null;
          $dao = CRM_Core_DAO::executeQuery($sql, $sqlParams);
          if ($dao->fetch()) {
            $total = $dao->total_amount;
            $receiveDate = $dao->receive_date;
          }

          // make the background color red if the amount is zero
          if ($total === 0) {
            $total = "<span style=\"background-color:red\">$total</span>";
          }

          if (array_key_exists("civicrm_receive_dates_receive_date_$year", $row)) {
            $rows[$i]["civicrm_receive_dates_receive_date_$year"] = $receiveDate;
          }

          $rows[$i][$k] = $total;
        }
      }

      $i++;
    }
  }

}
