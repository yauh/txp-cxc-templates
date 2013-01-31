txp-cxc-templates
=================

Textpattern plugin cxc_templates - adopted for compatibility with Textpattern 4.5.x

Setup
-----

By default, the plugin looks for the *tmp* and *tpl* directories, the *tpl* directory should be in the webroot with images, rpc, sites, and textpattern directories and the *tmp* directory should be in the textpattern directory. In a multi-site environment the *tpl* directory should inside the /public directory.

If the directories don’t exist, the plugin will attempt to create them the first time you access the plugin. This creation will sometimes fail, if that occurs, you’ll need to create the directories manually, and ensure that the web server has write access.