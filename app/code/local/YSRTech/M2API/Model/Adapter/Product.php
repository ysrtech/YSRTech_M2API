<?php

class YSRTech_M2api_Model_Adapter_Product
{
    public function toArray(Mage_Catalog_Model_Product $product)
    {
        $stockItem = $product->getStockItem();
        $categoryIds = $product->getCategoryIds();
        
        $data = array(
            'id' => (int)$product->getId(),
            'sku' => $product->getSku(),
            'name' => $product->getName(),
            'attribute_set_id' => (int)$product->getAttributeSetId(),
            'price' => (float)$product->getPrice(),
            'status' => (int)$product->getStatus(),
            'visibility' => (int)$product->getVisibility(),
            'type_id' => $product->getTypeId(),
            'created_at' => $product->getCreatedAt(),
            'updated_at' => $product->getUpdatedAt(),
            'weight' => (float)$product->getWeight(),
            'extension_attributes' => array(
                'website_ids' => $product->getWebsiteIds(),
                'category_links' => array_map(function($id) {
                    return array('category_id' => (string)$id);
                }, $categoryIds),
                'stock_item' => $stockItem ? array(
                    'item_id' => (int)$stockItem->getId(),
                    'product_id' => (int)$stockItem->getProductId(),
                    'stock_id' => (int)$stockItem->getStockId(),
                    'qty' => (float)$stockItem->getQty(),
                    'is_in_stock' => (bool)$stockItem->getIsInStock(),
                    'is_qty_decimal' => (bool)$stockItem->getIsQtyDecimal(),
                    'manage_stock' => (bool)$stockItem->getManageStock(),
                    'min_qty' => (float)$stockItem->getMinQty(),
                    'min_sale_qty' => (float)$stockItem->getMinSaleQty(),
                    'max_sale_qty' => (float)$stockItem->getMaxSaleQty()
                ) : null
            )
        );

        // Add custom attributes
        if ($product->getDescription()) {
            $data['custom_attributes'][] = array('attribute_code' => 'description', 'value' => $product->getDescription());
        }
        if ($product->getShortDescription()) {
            $data['custom_attributes'][] = array('attribute_code' => 'short_description', 'value' => $product->getShortDescription());
        }
        
        return $data;
    }

    public function toSimpleArray(Mage_Catalog_Model_Product $product)
    {
        return array(
            'id' => (int)$product->getId(),
            'sku' => $product->getSku(),
            'name' => $product->getName(),
            'price' => (float)$product->getPrice(),
            'status' => (int)$product->getStatus(),
            'type_id' => $product->getTypeId()
        );
    }
}
