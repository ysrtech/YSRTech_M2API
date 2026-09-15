<?php
// app/code/local/YSRTech/M2API/Model/Adapter/Product.php

/**
 * Magento\Catalog\Api\Data\ProductInterface, with the website_ids,
 * category_links and stock_item extension attributes, media gallery entries
 * and the common custom_attributes.
 */
class YSRTech_M2API_Model_Adapter_Product extends YSRTech_M2API_Model_Adapter_Abstract
{
    public function toArray(Mage_Catalog_Model_Product $product)
    {
        $data = array('id' => (int)$product->getId());
        $data += $this->export($product, $this->typed(
            array('sku', 'name', 'type_id', 'created_at', 'updated_at'),
            array('attribute_set_id', 'status', 'visibility'),
            array('price', 'weight')
        ));

        $categoryIds = $product->getCategoryIds();

        $data['extension_attributes'] = array(
            'website_ids'    => array_map('intval', (array)$product->getWebsiteIds()),
            'category_links' => $this->categoryLinks($product, $categoryIds),
        );
        $stockItem = $this->stockItem($product);
        if ($stockItem) {
            $data['extension_attributes']['stock_item'] = $stockItem;
        }

        $data['product_links'] = array();
        $data['options'] = array();
        $data['media_gallery_entries'] = $this->mediaGalleryEntries($product);
        $data['tier_prices'] = array();
        $data['custom_attributes'] = $this->customAttributes($product, $categoryIds);

        return $data;
    }

    /**
     * M2's GET /V1/products returns the full ProductInterface per row.
     */
    public function toSimpleArray(Mage_Catalog_Model_Product $product)
    {
        return $this->toArray($product);
    }

    /**
     * Magento\Catalog\Api\Data\CategoryLinkInterface[]: category_id is a
     * string in M2, position an int.
     */
    protected function categoryLinks(Mage_Catalog_Model_Product $product, array $categoryIds)
    {
        if (!$categoryIds) {
            return array();
        }
        $resource = Mage::getSingleton('core/resource');
        $read = $resource->getConnection('core_read');
        $select = $read->select()
            ->from($resource->getTableName('catalog/category_product'), array('category_id', 'position'))
            ->where('product_id = ?', (int)$product->getId());
        $positions = $read->fetchPairs($select);

        $links = array();
        foreach ($categoryIds as $categoryId) {
            $links[] = array(
                'position'    => isset($positions[$categoryId]) ? (int)$positions[$categoryId] : 0,
                'category_id' => (string)$categoryId,
            );
        }
        return $links;
    }

    protected function stockItem(Mage_Catalog_Model_Product $product)
    {
        $stockItem = $product->getStockItem();
        if (!$stockItem || !$stockItem->getId()) {
            $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product);
        }
        if (!$stockItem || !$stockItem->getId()) {
            return null;
        }
        return array(
            'item_id'        => (int)$stockItem->getId(),
            'product_id'     => (int)$stockItem->getProductId(),
            'stock_id'       => (int)$stockItem->getStockId(),
            'qty'            => (float)$stockItem->getQty(),
            'is_in_stock'    => (bool)$stockItem->getIsInStock(),
            'is_qty_decimal' => (bool)$stockItem->getIsQtyDecimal(),
            'manage_stock'   => (bool)$stockItem->getManageStock(),
            'min_qty'        => (float)$stockItem->getMinQty(),
            'min_sale_qty'   => (float)$stockItem->getMinSaleQty(),
            'max_sale_qty'   => (float)$stockItem->getMaxSaleQty(),
        );
    }

    /**
     * Magento\Catalog\Api\Data\ProductAttributeMediaGalleryEntryInterface[].
     * Collection-loaded products don't carry the gallery, so pull it through
     * the attribute backend rather than reloading the whole product.
     */
    protected function mediaGalleryEntries(Mage_Catalog_Model_Product $product)
    {
        if (!$product->hasData('media_gallery')) {
            $attribute = Mage::getSingleton('eav/config')
                ->getAttribute(Mage_Catalog_Model_Product::ENTITY, 'media_gallery');
            if ($attribute && $attribute->getId()) {
                $attribute->getBackend()->afterLoad($product);
            }
        }

        $gallery = $product->getMediaGallery('images');
        if (!is_array($gallery)) {
            return array();
        }

        $roles = array(
            'image'       => $product->getImage(),
            'small_image' => $product->getSmallImage(),
            'thumbnail'   => $product->getThumbnail(),
        );

        $entries = array();
        foreach ($gallery as $image) {
            $types = array();
            foreach ($roles as $role => $file) {
                if ($file && $file === $image['file']) {
                    $types[] = $role;
                }
            }
            $entries[] = array(
                'id'         => (int)$image['value_id'],
                'media_type' => 'image',
                'label'      => isset($image['label']) && $image['label'] !== '' ? $image['label'] : null,
                'position'   => isset($image['position']) ? (int)$image['position'] : 0,
                'disabled'   => !empty($image['disabled']),
                'types'      => $types,
                'file'       => $image['file'],
            );
        }
        return $entries;
    }

    /**
     * The attributes M2 surfaces as custom_attributes for a plain product.
     * Values are strings (category_ids a string list), like M2.
     */
    protected function customAttributes(Mage_Catalog_Model_Product $product, array $categoryIds)
    {
        $codes = array(
            'image', 'small_image', 'thumbnail', 'options_container', 'url_key', 'required_options',
            'has_options', 'meta_title', 'meta_keyword', 'meta_description', 'tax_class_id',
            'short_description', 'description',
        );

        $attributes = array();
        foreach ($codes as $code) {
            $value = $product->getData($code);
            if ($value === null || $value === '') {
                continue;
            }
            $attributes[] = array('attribute_code' => $code, 'value' => (string)$value);
        }
        $attributes[] = array('attribute_code' => 'category_ids', 'value' => array_map('strval', $categoryIds));
        return $attributes;
    }
}
