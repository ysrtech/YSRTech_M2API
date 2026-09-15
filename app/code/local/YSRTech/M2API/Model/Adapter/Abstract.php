<?php
// app/code/local/YSRTech/M2API/Model/Adapter/Abstract.php

/**
 * Shared field mapping for the Magento 2 data interfaces
 * (Magento\Sales\Api\Data\*, Magento\Catalog\Api\Data\*).
 *
 * Each adapter declares the M2 field list with a type, plus any M1 column
 * that carries the value under a different name. Only fields that are
 * actually set are emitted, which is how M2's REST layer behaves.
 */
abstract class YSRTech_M2API_Model_Adapter_Abstract
{
    /**
     * @param  Varien_Object $object   the M1 model
     * @param  array         $fields   m2_key => 'int'|'float'|'bool'|'string'
     * @param  array         $aliases  m2_key => m1_key for renamed columns
     * @return array
     */
    protected function export(Varien_Object $object, array $fields, array $aliases = array())
    {
        $out = array();
        foreach ($fields as $key => $type) {
            $source = isset($aliases[$key]) ? $aliases[$key] : $key;
            $value = $object->getData($source);
            if ($value === null) {
                continue;
            }
            $out[$key] = $this->cast($value, $type);
        }
        return $out;
    }

    protected function cast($value, $type)
    {
        switch ($type) {
            case 'int':
                return (int)$value;
            case 'float':
                return (float)$value;
            case 'bool':
                return (bool)$value;
            default:
                return (string)$value;
        }
    }

    /**
     * Build a typed field list from plain name lists.
     */
    protected function typed(array $strings, array $ints = array(), array $floats = array())
    {
        $fields = array();
        foreach ($strings as $key) {
            $fields[$key] = 'string';
        }
        foreach ($ints as $key) {
            $fields[$key] = 'int';
        }
        foreach ($floats as $key) {
            $fields[$key] = 'float';
        }
        return $fields;
    }

    /**
     * M1 stores tax compensation as "hidden tax"; M2 renamed the columns.
     */
    protected function hiddenTaxAliases()
    {
        return array(
            'discount_tax_compensation_amount'              => 'hidden_tax_amount',
            'base_discount_tax_compensation_amount'         => 'base_hidden_tax_amount',
            'shipping_discount_tax_compensation_amount'     => 'shipping_hidden_tax_amount',
            'base_shipping_discount_tax_compensation_amnt'  => 'base_shipping_hidden_tax_amnt',
            'discount_tax_compensation_invoiced'            => 'hidden_tax_invoiced',
            'base_discount_tax_compensation_invoiced'       => 'base_hidden_tax_invoiced',
            'discount_tax_compensation_refunded'            => 'hidden_tax_refunded',
            'base_discount_tax_compensation_refunded'       => 'base_hidden_tax_refunded',
            'discount_tax_compensation_canceled'            => 'hidden_tax_canceled',
        );
    }
}
