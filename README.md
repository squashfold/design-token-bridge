# Design Token Bridge for WordPress
This plugin is intended for use when exporting variables out of Figma and other design tools. The plugin takes variables in JSON format and parses the JSON output into vanilla CSS variables. This allows for greater parity between design and development teams where the majority of styling updates to themes can be automated based on updates made in the design tool.

## Installation
1. [Download the latest release here](https://github.com/squashfold/design-token-bridge/releases)
2. Unzip the folder
3. Place the design-token-bridge folder within your WordPress plugins folder
4. In the WordPress admin panel, go to Plugins and enable Design Token Bridge

## How to use
### Figma
This Plugin supports the following Figma plugins:
* [Variables Import Export](https://www.figma.com/community/plugin/1254848311152928301)
* [Export/Import Variables](https://www.figma.com/community/plugin/1256972111705530093)

Other plugins which export to JSON format may also work, but are not officially supported.

* Use this plugin or any similar plugin which exports to JSON format.
* Paste the generated JSON into the plugins settings and click 'Import and Save'
* The output is shown below the save button and will be loaded into the <head> tag of all pages
* Setup your modules to use the tokens as in designs, this will allow for them to automatically infer updates when new JSON is imported.

### Other Editors
As long as you are able to export variables in JSON format, you should be able to import them using this plugin. The plugin can handle nested JSON objects and will build the new CSS variables accordingly. For example, if the JSON looks like this:

```
{
  "Typography": {
    "Size": {
      "Heading 1": {
        "$type": "number",
        "$value": 64
      },
```

The output CSS variable would be `--typography-size-heading-1: 4rem;` 

Assuming you have valid JSON, follow these steps:
* Paste the generated JSON into the plugins settings and click 'Import and Save'
* The output is shown below the save button and will be loaded into the <head> tag of all pages
* Setup your modules to use the tokens as in designs, this will allow for them to automatically infer updates when new JSON is imported.

### Plugin Useage in WordPress
Once enabled, the plugin has an option page which allows for configuration of the imported JSON. The settings page under 'Design Token Bridge' has the following options:
* Exported JSON
  * Enter your token JSON here
* Enable PX to REMs conversion
  * Check this to automatically convert pixel units into REMS. If this is unchecked 'px' is used instead
* Base Font Size
    * If using REMs set this to your base font size for automatic REMs calculation

## Feature Requests and Bug Reporting
If you would like to request support of a new feature or found a bug please [submit a ticket](https://github.com/squashfold/design-token-bridge/issues/new/choose)

