# de.systopia.campaign
SYSTOPIA's Google Summer of Code project: CiviFundraiser. See [description](http://wiki.civicrm.org/confluence/display/CRM/Google+Summer+of+Code+-+2015#GoogleSummerofCode-2015-StrategicFundraisingandCampaigning)

# Quickstart Guide

## Installation
1. (If not already done) [Enable extensions](http://wiki.civicrm.org/confluence/display/CRMDOC/Extensions) in CiviCRM
2. Navigate to the extension folder and either
  * ```git clone https://github.com/systopia/de.systopia.campaign``` or
  * download and unzip the current release into it
3. In CiviCRM go to "Administer" > "System Settings" >"Manage Extensions"
4. You should see the extension in the list, if not click on "refresh"
5. Enable the extension

## Usage
After the extension has been enabled every campaign entry on the campaign dashboard (```/civicrm/campaign```) contains a new link to the extended campaign dashboard. Click on ```View``` to navigate to it.

## Updating
In case of an update follow these steps in order to safely upgrade your installation:

1. In CiviCRM go to "Administer" > "System Settings" >"Manage Extensions"
2. *Disable* the extension
3. *Uninstall* the extension
4. Navigate to the extension folder and either
  * ```git pull``` or
  * remove the current files, download and unzip the updated release into it
5. In CiviCRM go to "Administer" > "System Settings" >"Manage Extensions"
6. *Install* and then *enable* the extension

## Documentation
Follow [this link](https://github.com/systopia/de.systopia.campaign/blob/master/USERDOC.md) to get to the full version of the documentation
