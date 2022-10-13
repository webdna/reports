# Reports

## Installation

Install the plugin either via the plugin store or via composer:

`composer require webdna\reports && php craft plugin\install reports`


## Overview

This plugin allows a user to create/generate/view/export reports based on a report type. 

These report types are created as twig templates in the frontend templates folder allowing the website developer full control over the report types. 

A few basic example types are packaged in the plugin for coping in to the report types folder.


## Settings

The only setting is the location within the frontend template folder to the report types folder. If not specified a default location of `/_reports` will be used.


## Usage

Please see the example report types.