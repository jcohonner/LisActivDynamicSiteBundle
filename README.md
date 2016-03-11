# LisActivDynamicSiteBundle

## Purpose

eZ Publish supports multi-site configuration by design since years. Unfortunately, creating a new site requires setting files changes. This means a whole deployment process if you want to respect some standards like not changing production config files on the fly.

When multi-site means multiple different sites with different design, configuration, controllers (etc.) this doesn't really matter. You will have to go through a development process in any case.

If for your project, multi-site means the same exact site with only different content and maybe a bit of customization, it quickly becomes annoying to have to deploy config files.

That’s where LisActivDynamicSiteBundle may help you.

This bundle searches for specific content type (“site settings”) that describes sites and their configuration (name, domain, root). This configuration is dumped in a YML file that is loaded using Synfony DependecyInjection methods.

## Warning

LisActivDynamicSiteBundle is at a “Proof Of Concept Stage” this is not ready for production but may be used as a starting point for your own use.

## Requirements

eZ Publish 5.3

## Installation

Download Bundle and unzip in your src directory
Add LisActivDynamicSiteBundle to your EzPublishKernel
Create a site settings class

### Site Settings Content Type

You will need a “site settings”. You can customize content type identifier or fields identifiers in dynamicsite_parameter.yml

Default values are:

* Content type: site_settings
* Root field (eZ Relation Object): root
* Domain (only match method supported): domain

## Commands


To dump configuration:

```
php ezpublish/console dynamicsite:dump
```

## Todo

* Better dump file management (where, how..)
* Trigger publish event to run dump
* Support more site settings
* Support more match methods


