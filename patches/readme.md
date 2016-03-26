#Patches

## search_api_attachments
 - schema issues
 - issue: https://www.drupal.org/node/2694561
 - file: search_api_attachments/search_api_attachments-schema-issues-2694561-2-D8.patch

## drupal
 - composer update not working
 - issue: https://www.drupal.org/node/2664274#comment-10942761
 - file: patches/drupal/2664274-19-fix-composer.patch

## drupal
 - Search API not working on Acquia Cloud
 - issue: https://www.drupal.org/node/2669418
 - file: patches/drupal/2669418-16.patch
   
# How to patch

When patching a contrib module, the following steps should be followed:
1. Copy the patch file in this folder: <module_name>/<patch_file>
2. Apply the patch to the module
3. Update this readme with the reason and a link to the issue
4. Commit
