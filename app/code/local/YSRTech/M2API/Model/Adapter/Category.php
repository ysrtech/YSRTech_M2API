<?php

class YSRTech_M2api_Model_Adapter_Category
{
    public function toArray(Mage_Catalog_Model_Category $category)
    {
        return array(
            'id' => (int)$category->getId(),
            'parent_id' => (int)$category->getParentId(),
            'name' => $category->getName(),
            'is_active' => (bool)$category->getIsActive(),
            'position' => (int)$category->getPosition(),
            'level' => (int)$category->getLevel(),
            'product_count' => (int)$category->getProductCount(),
            'children_data' => array(),
            'created_at' => $category->getCreatedAt(),
            'updated_at' => $category->getUpdatedAt(),
            'path' => $category->getPath(),
            'include_in_menu' => (bool)$category->getIncludeInMenu(),
            'custom_attributes' => array(
                array('attribute_code' => 'description', 'value' => $category->getDescription()),
                array('attribute_code' => 'url_key', 'value' => $category->getUrlKey()),
                array('attribute_code' => 'url_path', 'value' => $category->getUrlPath())
            )
        );
    }
}
