<?php
class Gwiusa_Bidding_Block_Adminhtml_Bidding_Grid extends Mage_Adminhtml_Block_Widget_Grid {
    public function __construct() {
        parent::__construct();
        $this->setId('gwibidding');
        $this->setUseAjax(true);
        $this->setDefaultSort('bidding_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        //$this->setFilterVisibility(false);
        //$this->setPageerVisibility(false);
    }

    protected function _prepareCollection() {
        $collection = Mage::getModel('gwibidding/bidding')->getCollection();
        $prefix = Mage::getConfig()->getTablePrefix();
        $eavAttribute = new Mage_Eav_Model_Mysql4_Entity_Attribute();
        $pro_att_id = $eavAttribute->getIdByCode("catalog_product","name");

        $collection->getSelect()->join(array("pn" => $prefix."catalog_product_entity_varchar"), "pn.entity_id = main_table.product_id", array("proname"=>"value"))->where("pn.attribute_id = ".$pro_att_id. " AND pn.store_id = ".Mage::app()->getStore()->getStoreId());
        $collection->addFilterToMap("proname","pn.value");

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns() {
        $this->addColumn('bidding_id', array(
            'header'        =>  Mage::helper('gwibidding')->__('ID'),
            'align'         =>  'right',
            'width'         =>  '50px',
            'index'         => 'bidding_id',
        ));

        $this->addColumn('email', array(
            'header'         =>  Mage::helper('gwibidding')->__('Customer email'),
            'align'         =>  'left',
            'width'         =>  '230px',
            'index'         =>  'email',
        ));

        $this->addColumn('company', array(
            'header'    =>Mage::helper('gwibidding')->__('Company Name'),
            'align'     =>'left',
            'width'     =>'230px',
            'index'     =>'company',
        ));
        
        $this->addColumn('product_id', array(
            'header'    => Mage::helper('gwibidding')->__('Product'),
            'align'     =>'left',
            'index'     => 'proname',
        ));

        $this->addColumn('sku', array(
            'header'         =>  Mage::helper('gwibidding')->__('SKU'),
            'align'         =>  'left',
            'width'         =>  '200px',
            'index'         =>  'sku',
        ));

        $this->addColumn('qty', array(
            'header'         =>  Mage::helper('gwibidding')->__('Quantity'),
            'align'         =>  'right',
            'index'         =>  'qty',
        ));

        $this->addColumn('unit_price', array(
            'header'         =>  Mage::helper('gwibidding')->__('Unit Price'),
            'align'         =>  'right',
            'index'         =>  'unit_price',
        ));

        $this->addColumn('unit_price_offerred', array(
            'header'         =>  Mage::helper('gwibidding')->__('Unit Price Offered'),
            'align'         =>  'right',
            'index'         =>  'unit_price_offerred',
        ));

        $this->addColumn('expect_date', array(
            'header'         =>  Mage::helper('gwibidding')->__('Expected Shipping Date'),
            'align'         =>  'left',
            'index'         =>  'expect_date',
        ));

        $this->addColumn('accepted', array(
            'header'         =>  Mage::helper('gwibidding')->__('Counter Offer Status'),
            'align'         =>  'right',
            'index'         =>  'accepted',
        ));

        $this->addColumn('created_at', array(
            'header'         =>  Mage::helper('gwibidding')->__('Created At'),
            'align'         =>  'right',
            'index'         =>  'created_at',
        ));

        $this->addColumn('status', array(
            'header'         =>  Mage::helper('gwibidding')->__('Status'),
            'align'         =>  'left',
            'index'         =>  'status',
        ));

        $this->addColumn('last_modified_by', array(
            'header'         =>  Mage::helper('gwibidding')->__('Modified By'),
            'align'         =>  'right',
            'index'         =>  'last_modified_by',
        ));

        $this->addColumn('action',
            array(
                'header'    =>  Mage::helper('gwibidding')->__('Action'),
                'width'     => '100',
                'type'      => 'action',
                'getter'    => 'getBiddingId',
                'actions'   => array(
                    array(
                        'caption'   => Mage::helper('gwibidding')->__('Edit Bid'),
                        'url'       => array('base'=> '*/*/edit'),
                        'field'     => 'id'
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'stores',
                'is_system' => true,
        ));

       $this->addExportType('*/*/exportCsv', Mage::helper('gwibidding')->__('CSV'));
       $this->addExportType('*/*/exportXml', Mage::helper('gwibidding')->__('XML'));

       return parent::_prepareColumns();


    }
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('gwibidding_id');
        $this->getMassactionBlock()->setFormFieldName('gwibiddings');

        $this->getMassactionBlock()->addItem('delete', array(
             'label'    => Mage::helper('gwibidding')->__('Delete'),
             'url'      => $this->getUrl('*/*/massDelete'),
             'confirm'  => Mage::helper('gwibidding')->__('Are you sure?')
        ));

        return $this;
    }

  public function getRowUrl($row)
  {
      return $this->getUrl('*/*/edit', array('id' => $row->getId()));
  }

}
