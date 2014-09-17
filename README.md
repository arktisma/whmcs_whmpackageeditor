# whmcs_whmpackageeditor

This is an addon module for WHMCS (5.3.9+) which allows you to quickly edit disk and bandwidth limits cPanel hosting packages from a single interface.

It allows for simultaneous editing of:

* The product description in WHMCS
* The nominal disk and bandwidth of a package in WHMCS, and the overage limits if applicable
* The disk and bandwidth limits in the related WHM package

## Installation

Extract so that the module is installed within `$WHMCS_ROOT/modules/addons/whmpackageeditor`.

Next, you will need to install any PHP dependencies. Navigate to the addon folder, and run `composer install`. If you do not have Composer, find it at [getcomposer.org](https://getcomposer.org).

In WHMCS admin, navigate to Setup->Addon Modules, and 'Activate' the package. Ensure that the correct 'Access Controls' are set, so that you can access the menu item.

If this is done correctly, the 'WHM Package Editor' should appear in the 'Addons' menu.

## Caveats

* Product/package pairs already need to exist, so WHMCS 'Plan A' linked to WHM package 'plan_a' need to have already been created. This will not install packages to servers that do not already have them.
* Overages are partially supported - to enable/disable overages, please do it from the WHMCS product config, and then use this addon to sync it through to WHM
* This will update packages on ANY cPanel server (defined in WHMCS) that has a matching package (by name).
