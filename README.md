# EcoRide

EcoRide est une application web de covoiturage écologique, uniquement en voiture, avec une interface intuitive et respectueuse de l'environnement.

## Prérequis

- PHP 8.2.12  
- Symfony CLI  
- Composer  
- MySQL  
- Navigateur web moderne

## Installation

```bash
git clone https://github.com/Gaedolen/EcoRide
cd EcoRide
composer install
```
## Base de données

php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

## Lancer l'application

symfony server:start

Accéder à : https://localhost:8000/accueil

## Fonctionnalités principales

- Gestion des comptes utilisateurs
- Recherche et ajout de covoiturages
- Filtrage des trajet
- Avis sur les trajet
- Signaler un utilisateur

## Technologies

- Symfony
- PHP
- MySQL
- Composer
- Twig
- JavaScript
- HTML
- CSS