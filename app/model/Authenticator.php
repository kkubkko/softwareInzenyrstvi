<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$acl = new Permission;
// definujeme role


$acl->addRole('admin'); // Přidat z DB? - tohle teda netuším co má být celej tady ten soubor... 
                            //ale myslím si že to je jenom testovací soubor když není vytvořená DB 
                            //aby se vytvořily nějaký dočasný instance
$acl->addRole('user');
$acl->addRole('projektant');



// definujeme zdroje
$acl->addResource('file'); // WTF



// pravidlo: host může jen prohlížet články
$acl->allow('guest', 'article', 'view');
// pravidlo: člen může prohlížet vše, soubory i články
$acl->allow('member', Permission::ALL, 'view');
// administrátor dědí práva od člena, navíc má právo vše editovat
$acl->allow('administrator', Permission::ALL, array('view', 'edit'));
// zaregistrujeme autorizační handler


Environment::getUser()->setAuthorizationHandler($acl); 

