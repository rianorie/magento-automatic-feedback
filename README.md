# Reviewo Automatic Feedback Extension for Magento.

This magento extension automatically sends all your Magento orders to Reviewo for use with the "Automatic Feedback" service.

## Table of contents

 - [Installation](#installation)
 - [Usage](#usage)
 - [Support](#support)
 - [Authors](#authors)
 - [Copyright and licence](#copyright-and-licence)

## Installation

Installation can be performed by one of two methods

 - [Magento Connect extension key](#magento-connect-extension-key)
 - [Magento Connect direct package upload](#magento-connect-direct-package-upload)
 - [Manual installation](#manual-installation)

### Magento Connect extension key

To install via magento connect use the extension key:
`http://connect20.magentocommerce.com/community/Reviewo_AutomaticFeedback`

### Magento Connect direct package upload

Login to your Magento Connect Manager. Under 'Direct package file upload' you can 'Upload package file', click the 'Choose File button' and upload this file: [Reviewo_AutomaticFeedback-1.3.3.tgz](https://github.com/reviewo/magento-automatic-feedback/raw/v1.3.3/release/Reviewo_AutomaticFeedback-1.3.3.tgz)

### Manual installation

Download the [latest stable release](https://github.com/reviewo/magento-automatic-feedback/raw/v1.3.3/release/Reviewo_AutomaticFeedback-1.3.3.tgz) and unzip to to your root Magento directory

## Usage

Usage simply requires providing your API key to the extension. Login to your magento installation and navigate to `System > Configuration > Sales`. Under the `Reviewo Automatic Feedback` heading enter your API key that has been provided.

## Support

For all support requests regarding this extension please email <support@reviewo.com>

## Versioning

For transparency into our release cycle and in striving to maintain backward compatibility, this extension is maintained under the Semantic versioning guidelines. Sometimes we screw up, but we'll adhere to these rules whenever possible.

Releases will be numbered with the following format:

`<major>.<minor>.<patch>`

And constructed with the following guidelines:

- Breaking backward compatibility **bumps the major** while resetting minor and patch
- New additions without breaking backward compatibility **bumps the minor** while resetting the patch
- Bug fixes and misc changes **bumps only the patch**

For more information on SemVer, please visit <http://semver.org/>.

## Authors

**Leon smith**

- <https://twitter.com/leonmarksmith>
- <https://github.com/leonsmith>

## Copyright and licence
Code and documentation copyright 2014 Reviewo Ltd.
Code and documentation released under [The Open Software License 3.0](LICENCE).
