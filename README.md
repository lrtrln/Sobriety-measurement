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
