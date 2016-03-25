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
* Default User Placement field field (eZ Relation Object): default_user_placement
* User Content Type ID : user_content_type_id (must be unique per siteaccess to allow e-mail login)

## Commands


To dump configuration:

```
php ezpublish/console dynamicsite:dump
```

Then clear cache

## Todo

* Better dump file management (where, how..)
* Trigger publish event to run dump
* Support more site settings
* Support more match methods
* Run config cache clear after dump
* Add siteaccess group parameter


## Additional POC : poc_user Branch

eZ Publish Legacy supports :
- e-mail authentication
- non unique e-mails
- per siteaccess "UserSettings/SiteName" as some kind of salt on hash method (md5_site : MD5 (login\npassword\nsiteName))

Since eZ Publish 5.3, the login action is handled by a new method based on Symfony standards. Unfortunately what was working previouly is not supported anymore :
- only login authentication
- SiteName is not dynamically taken and "ez.no" is someway hardcoded

This POC (in the POC) aims to bring back these features required for our project

_Important_

When allowing non unique e-mails, to ensure unicity of user per site, it is necessary to use different user content type per siteaccess. Please refer to ezsettings.default.user_content_type_id.

You should enhance register to check existing e-mails for this particular content type

Step 1 : create settings and inject into legacy

These are added in dynamicsite_parameters.yml and in the dynamically generated parameters files for siteaccess override

```
    #Additional settings for Legacy and Login Action
    ezsettings.default.site_name: ez.no
    #Additional settings for Legacy and Login Action
    ezsettings.default.default_user_placement: 12
    #Additional settings for Legacy User content type (class in legacy)
    ezsettings.default.user_content_type_id: 4
```

If using the dynamicsite generator, these data will be included in the dump per siteaccess. Otherwise just override it in your configuration

```
    ezsettings.<siteaccess>.user_content_type_id: 4
```

Step 2 : Inject Into Legacy

see LisActiv\Bundle\DynamicSiteBundle\LegacyMapper\Configuration

From that point you can create users with the relevant SiteName but login will fail as the hash comparaison fails.

Step 3 : Override RepositoryFactory

After many tries, I appeared that UserService is hardcoded with ez.no as siteName. User Service class is itself hardcoded (even if a parameter exists, it is not used in the Reprository Class in getUserService function), same as Repository. So the best way I found was to override RepositoryFactory and pass the dynamic settings from that class.

see LisActiv\Bundle\DynamicSiteBundle\Service\LisActivRepositoryFactory

service.yml

```
ezpublish.api.repository.factory.class: LisActiv\Bundle\DynamicSiteBundle\Service\LisActivRepositoryFactory
```

From that point you can login using login not e-mail

Step 4 :

Create a new Authentication Provider (will test users with given e-mail one by one, first returned. Only one will validate e-mail+hash when siteName is correctly set)

see security.yml and associated classes

(Thanks to Silver Solutions : on http://blog.silversolutions.de/2014/07/ezpublish/extend-ez-5-3-login-email/)


