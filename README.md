wiki_restricted_groups
======================

MediaWiki plugin that restrict groups to read only configured categories.


Requirements
------------

It requires:

- MediaWiki >= 1.18

Tested with:

- MediaWiki == 1.21


Install
-------

Download this plugin in the MediaWiki **extensions** directory.

```bash
cd MEDIA_WIKI_ROOT/extensions
git clone https://github.com/rarylson/wiki_restricted_groups.git
mv wiki_restricted_groups restricted_groups
```

Create the config file:

    cd MEDIA_WIKI_ROOT/extensions/restricted_groups
    cp restricted_groups.conf.php.sample restricted_groups.conf.php

Add these lines to **LocalSettings.php**:

```php
# rectricted groups plugin
# See: https://github.com/rarylson/wiki_restricted_groups
require_once "$IP/extensions/restricted_groups/restricted_groups.php";
```

How to
------

Edit the **restricted\_groups.conf.php** file with your restricted groups and allowed categories:

    // Array with restricted groups
    $wgVlkRestrictGroup = array( 'receptionist', 'client1' );

    // Array with group/categories restrictions
    $wgVlkRestrictGroupRules['receptionist'] = array( 'Utils', 'Receptionist' );
    $wgVlkRestrictGroupRules['client1'] = array( 'Client1' );

Add some users to one of these groups. For example, the **Client1user** to the **client1** group.

Finally, add some page to **Client1** category. You can do it adding this line in the page source code:

    [[Category:Client1]]

Now, **Client1user** can read only some [single pages](#security) and the pages with `[[Category:Client1]]`.


Security
--------

Supose a restricted group that can access the **EXAMPLE** category. Only these pages can be read by the restricted group:

- **Special:Userlogin:** The login page;
- **Special:Userlogout:** Permit user logout;
- **Special:PasswordReset:** User can reset your password;
- **Category:EXAMPLE:** The main page of the category EXAMPLE. The user can see all pages that belong to this category here;
- **Pages in EXAMPLE category:** Pages with `[[Category:EXAMPLE]]` in your source code.

In these pages, only the **read** privilege is allowed. All others privileges are revoked (like **edit** or **delete**).

This user can read a lot of static content. The restrictions are applied only to pages.

Because only the **read** priv is granted, there won't be problems with the [most common security problems](http://www.mediawiki.org/wiki/Security_issues_with_authorization_extensions) with MediaWiki authorization extensions.


License
-------

We use the [BSD 2 Clauses License](LICENSE). You're free to use this extension and crontrib will us.


TODO
----

- Add suport to allow the **Main Page**
- Restrict image and other static content access
- Allow **Change Password** page

