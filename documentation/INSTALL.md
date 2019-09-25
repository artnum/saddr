# INSTALL

 - Have smarty available as "Smarty/Smarty.class.php"
 - Have ldap installed

## Web

Install dojo and dijit :

```sh
$ npm install dojo
$ npm install dijit
```

Wherever you like

In saddr directory create conf/ directory with file saddr.index.local.php :

```php
<?PHP
saddr_setLdapHost($Saddr, "ldap://localhost"); // change to what you need
saddr_setUser($Saddr, ''); // ldap user name
saddr_setPass($Saddr, ''); // ldap password
?>
```

## LDAP

For user name and password for ldap server I usually create :

```
cn: saddr
userpassword: mypass
objectclass: applicationprocess
objectclass: simplesecurityobject
```

in an OU called "applications"

Add smarty configuration schema with 

```sh
$ ldapadd $AUTHOPTIONS -f $SADDRROOT/documentation/schema/saddr.ldif
```

Add iroAddiontalUserInfo.ldif and ldapab.ldif which is add objectclass created for an institute long time ago (that works with Mozilla Thunderbird)

```sh
$ ldapadd $AUTHOPTIONS -f $SADDRROOT/documentation/schema/iroAdditionalUserInfo.ldif
$ ldapadd $AUTHOPTIONS -f $SADDRROOT/documentation/schema/ldapab.ldif
```

Create an object, wherever you want in your tree, saddrConfiguration with, at least :

```
saddrConfigName # name of your confirguration
saddrBase # the ldap base for your adress book storage
saddrDojoWebPath # path to dojo install -> nodes_module/dojo/dojo.js
saddrDijitThemeWebPath # css to be included for dijit -> nodes_module/dijit/...
saddrDijitThemeName # name of the theme
```