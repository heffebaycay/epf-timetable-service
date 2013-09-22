EPF Timetable Service
=====================

Ce projet permet la synchronisation de l'emploi du temps présent sur le portail EPF portail.epf.fr avec le service Calendar de Google.


Mode de fonctionnement
----------------------

Une interface web permet aux utilisateurs de s'enregistrer sur le service en se connectant avec leur compte Google et en validant leur adresse e-mail EPF. La partie "synchronisation des emplois du temps" est prise en charge par une commande console devant être lancée régulièrement.


Installation
------------

Pour installer ce projet, vous devez disposer de [composer](http://getcomposer.org/). Une fois composer installé, il vous suffit d'appeler la commande suivante pour installer l'ensemble des dépendances du projet :

    php composer.phar install

Paramétrage
-----------

Un certain nombre de paramètres doivent être configurés pour que le système fonctionne. Ceux-ci sont listés dans le fichier `app/config/parameters.yml.dist`.

Base de données
---------------

Une fois la base de données configurée dans le fichier `parameters.yml`, celle-ci doit être générée via l'utilisation de la commande suivante : `php app/console doctrine:schema:update --force`
