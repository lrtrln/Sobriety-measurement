# Analyseur de Sobriété de Page Web

## Description

Ce projet consiste en un outil d'analyse de sobriété des pages web. Il évalue les éléments DOM, le poids des ressources et les requêtes pour aider à réduire la consommation énergétique et l'empreinte carbone des sites web.

## Fonctionnalités

- Analyse des éléments DOM
- Mesure du poids des ressources
- Évaluation des requêtes
- Nombre de requêtes HTML globales
- Temps de réponse serveur
- Temps de chargement de la page
- Affichage de la note de sobriété
- Génération de rapports détaillés des ressources (triées par poids)
- En option affichage scores Pagespeed desktop et mobile

## Méthode calcul score sobriété

Trois critères principaux : le nombre d'éléments DOM, le poids total des ressources en Mo et le nombre total de requêtes.

1. Chaque critère est converti en un score individuel en utilisant des formules spécifiques :

* calculateDomScore() calcule le score en fonction du nombre d'éléments DOM. Plus le nombre d'éléments est proche de la limite maximale, plus le score diminue.
* calculateWeightScore() calcule le score en fonction du poids total des ressources en Mo. Le score diminue à mesure que le poids total des ressources approche de la limite maximale.
* calculateRequestScore() calcule le score en fonction du nombre total de requêtes. Le score diminue à mesure que le nombre de requêtes approche de la limite maximale.
* Chaque score individuel est ensuite combiné pour obtenir un score total. En fonction de ce score total, une note de sobriété est attribuée :
* Si le score total est supérieur ou égal à 90, la note est 'A'.
* Si le score total est entre 80 et 89, la note est 'B'.
* Et ainsi de suite, avec des plages de scores correspondant à des notes de 'C' à 'G'.
* La méthode calculateNote() retourne la note de sobriété calculée en fonction du score total obtenu.

La note de sobriété est une évaluation globale de la performance et de l'efficacité d'une page web en termes de structure DOM, poids des ressources et nombre de requêtes, permettant de classer la sobriété de la page de 'A' à 'G' en fonction de ces critères.

## Installation

1. Cloner le dépôt
2. Exécuter `composer install` pour installer les dépendances PHP
3. Configurer le serveur web pour pointer vers le dossier public

## Utilisation

1. Accéder à l'outil via le navigateur
2. Entrer l'URL de la page à analyser
3. Soumettre le formulaire pour obtenir les résultats

## Auteur

Développé par lrtrln.


---

# Web Page Sobriety Analyzer

## Description

This project is a web page sobriety analysis tool. It evaluates DOM elements, resource weight, and requests to help reduce the energy consumption and carbon footprint of websites.

## Features

- DOM element analysis
- Resource weight measurement
- Request evaluation
- Global HTML request count
- Server response time
- Page load time
- Sobriety score display
- Generation of detailed resource reports (sorted by weight)
- Optional display of Pagespeed desktop and mobile scores

## Sobriety Score Calculation Method

Three main criteria: the number of DOM elements, the total weight of resources in MB, and the total number of requests.

1. Each criterion is converted into an individual score using specific formulas:

* `calculateDomScore()` calculates the score based on the number of DOM elements. The closer the number of elements is to the maximum limit, the lower the score.
* `calculateWeightScore()` calculates the score based on the total weight of resources in MB. The score decreases as the total resource weight approaches the maximum limit.
* `calculateRequestScore()` calculates the score based on the total number of requests. The score decreases as the number of requests approaches the maximum limit.
* Each individual score is then combined to obtain a total score. Based on this total score, a sobriety rating is assigned:
* If the total score is greater than or equal to 90, the grade is 'A'.
* If the total score is between 80 and 89, the grade is 'B'.
* And so on, with score ranges corresponding to grades from 'C' to 'G'.
* The `calculateNote()` method returns the calculated sobriety grade based on the total score obtained.

The sobriety score is a global evaluation of a web page's performance and efficiency in terms of DOM structure, resource weight, and number of requests, allowing the page's sobriety to be classified from 'A' to 'G' based on these criteria.

## Installation

1. Clone the repository
2. Run `composer install` to install PHP dependencies
3. Configure the web server to point to the public folder

## Usage

1. Access the tool via the browser
2. Enter the URL of the page to be analyzed
3. Submit the form to get the results

## Author

Developed by lrtrln.