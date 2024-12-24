# UP Theme Generator

Plugin WordPress pour la génération et la gestion de thèmes FSE (Full Site Editing).

## Fonctionnalités

### Gestion des Sections
- Création de presets de sections
- Personnalisation des couleurs (arrière-plan, texte, boutons, liens, titres)
- Prévisualisation en direct
- Support des blocs Gutenberg (Group, Columns, Cover)
- CRUD complet des presets

### Gestion de la Typographie
- Création de presets typographiques
- Support multi-polices (jusqu'à 3)
- Import de polices personnalisées
- Application aux thèmes existants

### Génération de Thèmes
- Création de thèmes FSE
- Mise à jour de thèmes existants
- Système de sauvegarde
- Gestion des captures d'écran

## Installation

1. Télécharger le plugin
2. L'installer via WordPress
3. Activer le plugin
4. Accéder via le menu "UP Theme Generator"

## Utilisation

### Créer un Preset de Section
1. Aller dans "Sections"
2. Sélectionner un thème
3. Configurer les couleurs
4. Choisir les types de blocs
5. Sauvegarder

### Gérer la Typographie
1. Aller dans "Typographie"
2. Créer un nouveau preset
3. Sélectionner les polices
4. Appliquer au thème

### Générer un Thème
1. Aller dans "Générer"
2. Remplir les informations
3. Choisir les options
4. Générer le thème

## Structure des Fichiers
```
up-theme-generator/
├── includes/
│   ├── class-section-manager.php
│   ├── class-typography-manager.php
│   ├── class-fonts-manager.php
│   └── class-theme-generator.php
├── templates/
│   ├── sections-page.php
│   └── sections-presets-list.php
├── assets/
│   ├── css/
│   └── js/
└── up-theme-generator.php
```

## Prérequis
- WordPress 6.0+
- PHP 7.4+
- Support FSE activé

## Support
- Documentation : [lien]
- Problèmes : [lien]
- Contact : [email]

## Licence
GPL v2 ou ultérieure

## Auteurs
- Développé par [nom]
- Contributeurs : [liste]

## Journal des Modifications

### 1.0.0
- Version initiale
- Support des sections
- Gestion des presets
- Interface d'administration

### 1.1.0
- Ajout gestion typographie
- Amélioration prévisualisation
- Corrections de bugs

## FAQ

**Q : Le plugin est-il compatible avec tous les thèmes ?**
R : Fonctionne uniquement avec les thèmes FSE.

**Q : Puis-je importer mes propres polices ?**
R : Oui, via l'interface de gestion des polices.

**Q : Les presets sont-ils exportables ?**
R : Oui, format JSON supporté.