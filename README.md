# magento-automatic-feedback

Reviewo Automatic Feedback Extension for Magento.
If you need any support email leon@reivewo.com


## Installation

Ditto or rsync the files across to your magento install.

````bash
ditto ~/magento-automatic-feedback/app/ /srv/www/magento/htdocs/app/
````

Now navigate to your magento admin area and click on `System » Configuration » Sales` and under
the **Reviewo Automatic Feedback** heading enter your api key and user.

You can disable the module from sending data without uninstalling it by navigating to
`System » Configuration » Advanced` and disabling `Reviewo_AutomaticFeedback`
