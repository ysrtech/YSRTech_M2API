# YSRTech_M2API

OpenMage/Magento 1 REST API module for exposing Magento 2 compatible endpoints.

## Description

This module provides a REST API interface for OpenMage (Magento 1) applications, allowing integration with external systems through token-based authentication.

## Features

- Token-based authentication
- REST API endpoints for Magento data
- Magento 2 compatible API structure
- Support for Catalog, Customer, and Sales data

## Installation

### Manual Installation

1. Copy the module files to your OpenMage installation:
   ```
   app/code/local/YSRTech/M2API/
   app/etc/modules/YSRTech_M2API.xml
   ```

2. Clear cache:
   ```
   rm -rf var/cache/*
   ```

3. The module should now be installed and active.

## Configuration

The module automatically creates the necessary database tables during installation.

## Dependencies

- Mage_Catalog
- Mage_Customer
- Mage_Sales

## Version

Current version: 1.0.0

## License

Please add your preferred license information here.

## Author

YSRTech

## Support

For issues and feature requests, please use the GitHub issue tracker.
