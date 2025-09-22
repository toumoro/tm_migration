FR
---

# NOM_DU_PROJET - v(FROM_VERSION) à v(TO_VERSION) Migration

> ## Préparation avant le déploiement en production

💡 **À compléter si nécessaire :**
_Étapes à réaliser avant le jour de migration par un <u>développeur</u>._

* Simuler l'ensemble du processus de migration sur l'instance DEV avec une nouvelle copie de la base de données et des fichiers pour s'assurer que tout est prêt comme prévu pour le déploiement en production.

> ## Le jour du déploiement en production

### 1. Geler les modifications de la base de données

* Bloquer l'accès au backend pour les éditeurs TYPO3 : [Documentation de référence](https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/Administration/SystemSettings/MaintenanceMode/Index.html)
* Afficher un message de maintenance pour tout formulaire contenant des données sensibles pouvant être stockées dans la base de données pendant le déploiement en production ou sur l'ensemble du site si nécessaire.

### 2. Copier la base de données de production

💡 **À compléter si nécessaire :**
_Étapes à réaliser par un <u>devops</u>_

* Cette étape consiste à copier les données depuis la base de données de production actuelle vers la nouvelle base de données.

### 3. Synchroniser les dossiers public/fileadmin et public/secure

💡 **À compléter si nécessaire :**
_Étapes à réaliser par un <u>devops</u>_

* Cette étape est seulement applicable si l'environnement de production est dupliqué. Elle n'est généralement pas applicable lorsque le fileadmin est sur S3.

### 4. Exécuter la procédure de migration TYPO3

💡 **À compléter si nécessaire :**
_Étapes à réaliser par un <u>devops</u>_

* Exécuter le script de migration :
  ```bash
  ./migration/run.sh
  ```
* Garder toutes les modifications dans le script de migration et éviter toute modification manuelle directement dans le backend. Mettre à jour le script dans la branche *migration* du git. **Des modifications manuelles peuvent être nécessaires, mais doivent être utilisées seulement en dernier recours.**

### 5. Vérifier le fonctionnement et l'intégration du site

💡 **À compléter si nécessaire :**
_Étapes à réaliser par un <u>développeur</u>_

#### 5.1. Effectuer les modifications manuellement dans le BE qui n'ont pas été intégrées à la procédure (à lister ici)

#### 5.2. Effectuez les vérifications indiquées, le cas échéant

- Réindexer solr et solrfal (si applicable).
- Vérifier que les packs de langues sont correctement installés.
- S'assurer que les utilisateurs backend (éditeurs) disposent des permissions appropriées, y compris les modules et mounts de base de données nécessaires pour accéder aux pages et aux fichiers.
- Vérifier les problèmes liés à la politique CSP (bucket S3 - si applicable).
- Vérifier l'intégrité des redirections TYPO3.
- Tester tout formulaire contenant des données sensibles pouvant être stockées dans la base de données (ex : formulaire de connexion ou d'inscription).

### 6. Basculer vers la version migrée

💡 **À compléter si nécessaire :**
_Étapes à réaliser par un <u>devops</u>_

### 7. Validation

💡 **À compléter si nécessaire :**
_Étapes à réaliser par un <u>développeur</u>_

- Effectuer des tests aléatoires pour valider le bon fonctionnement du site (s'appuyer sur les tickets de bugs résolus pour tester globalement).

EN
---

# PROJECT_NAME - v(FROM_VERSION) to v(TO_VERSION) Migration

> ## Preparation Before Production Deployment

💡 **To complete if necessary:**
_Steps to be performed before the migration day by a <u>developer</u>._

* Simulate the entire migration process on the DEV instance with a new copy of the database and files to ensure that everything is ready as planned for deployment to prodcution.

> ## On Production Deployment day

### 1. Freeze database changes

* Block access to the backend for TYPO3 editors : [Reference DOC](https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/Administration/SystemSettings/MaintenanceMode/Index.html)
* Display a maintenance message for any form containing sensitive data that may be stored in the database during production deployment or on the entire site if needed.

### 2. Copy Production Database

💡 **To complete if necessary:**
_Steps to be performed by a <u>devops</u>_

* This step involves copying the data from the current production database to the new database.

### 3. Synchronize the public/fileadmin and public/secure folders.

💡 **To complete if necessary:**
_Steps to be performed by a <u>devops</u>_

* This step is only applicable is the production environment is duplicates. It is generally not applicable when the fileadmin is on S3.

### 4. Execute TYPO3 Migration Procedure

💡 **To complete if necessary:**
_Steps to be performed by a <u>devops</u>_

* Run migration shell script:
  ```bash
  ./migration/run.sh
  ```
* Keep all changes in the migration script and avoid any manual changes directly in the backend. Keep the migration script up to date in the *migration* branch in git. **Manual changes might be necessary, but must be used only as a last resort.**.

### 5. Verify Website Functionality & Integration

💡 **To complete if necessary:**
_Steps to be performed by a <u>developer</u>_

#### 5.1. Manually make the changes in the BE that have not been included in the procedure (list them here)

#### 5.2. Perform the indicated checks, if applicable

- Reindex solr and solrfal (if applicable).
- Verify that language packs are installed correctly.
- Ensure that backend users (editors) have the appropriate permissions, including the required modules and database mounts for accessing pages and filelists.
- Check for issues related to CSP policy (S3 bucket - if applicable).
- Verify the integrity of TYPO3 redirects.
- Test any form containing sensitive data that may be stored in the database ( e.g: login or register form )

### 6. Switch To The Migrated Version

💡 **To complete if necessary:**
_Steps to be performed by a <u>devops</u>_


### 7. Validation

💡 **To complete if necessary:**
_Steps to be performed by a <u>developer</u>_

- Perform random tests to verify that the site is functioning correctly (rely on resolved bug tickets for global testing).