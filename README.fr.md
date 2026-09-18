# KioskLite

[English](README.md) | **Français**

KioskLite est un système léger de borne web et d’affichage dynamique pour
Raspberry Pi, conçu pour fonctionner même sur les modèles les plus anciens.

Écrivez l’image sur la carte SD, modifiez un fichier texte, allumez le Raspberry Pi — c’est tout.

KioskLite peut afficher n’importe quel site HTTP ou HTTPS en plein écran.
Il comprend également une application PHP d’affichage dynamique facultative,
avec gestion des diaporamas et interface d’administration accessible depuis un navigateur.

## Corrections testées les 17–18 septembre 2026 (nouvelle image à préparer)

Le plein écran sans barre d’adresse a été validé sur Pi2 et Pi Zero, au
démarrage et après relance automatique de Midori. La boucle corrigée active
la fenêtre Midori avant d’envoyer F11 à chaque lancement.

La configuration vidéo silencieuse testée utilise `ALSOFT_DRIVERS=null` :
une erreur d’initialisation du périphérique audio OpenAL bloquait auparavant
la lecture, même avec une vidéo muette. Ce contournement ne produit aucun son
via OpenAL.

Le Pi2 a affiché deux vidéos H.264 allégées en 640 × 360 simultanément.
Ce résultat ne garantit pas la lecture de tous les fichiers. Un clip Full HD
échouait ; sa copie réduite, en profil Baseline et sans audio, fonctionne.
Le test ne permet pas d’attribuer l’échec à la seule résolution.

Le Pi Zero fonctionne de manière satisfaisante avec les images. **Vidéos
déconseillées** : même une vidéo légère peut ralentir, se bloquer ou laisser
une zone blanche.

Le diaporama web corrigé passe à la suite en cas d’échec ou de blocage vidéo
et libère l’ancien lecteur. La durée configurable concerne les images ; une
vidéo qui progresse normalement est lue jusqu’à sa fin. Voir le
[guide médias et dépannage](docs/media.md) (en anglais).

Ces changements ont été testés sur les appareils et l’application web en
service. La nouvelle image reste à construire et à valider ; les informations
et la somme de contrôle de l’image v1.1.0 ci-dessous restent celles de cette
version publiée.

## Démarrage rapide

### 1. Écrire l’image KioskLite sur la carte SD

Téléchargez le fichier `kiosklite-imager.rpi-imager-manifest` disponible dans la [release KioskLite](https://github.com/devlabnet/KioskLite/releases), puis ouvrez-le avec Raspberry Pi Imager. Sélectionnez **KioskLite**, choisissez votre carte SD et lancez l’écriture de l’image.
Les options de personnalisation de Raspberry Pi Imager permettent de configurer
le nom d’hôte, le nom d’utilisateur et le mot de passe, SSH, les identifiants Wi-Fi
sur le matériel compatible, la disposition du clavier, le pays et le fuseau horaire.

Plusieurs redémarrages automatiques peuvent avoir lieu lors du premier démarrage. C’est normal.

### 2. Configurer le site web

Insérez la carte SD dans un ordinateur et ouvrez la partition `bootfs`.

Modifiez le fichier :

`kiosklite.conf`

Indiquez l’URL que KioskLite doit afficher :

`URL=https://www.example.com`

L’adresse doit commencer par `http://` ou `https://`.

Enregistrez le fichier et éjectez la carte SD.

### 3. Démarrer KioskLite

Insérez la carte SD dans le Raspberry Pi et allumez-le.

KioskLite démarre automatiquement son environnement graphique minimal et ouvre
le site web configuré dans Midori en plein écran.

Aucune connexion SSH ni configuration Linux n’est nécessaire pour modifier l’URL affichée.

### URL absente ou invalide

Si le fichier `kiosklite.conf` est absent, si `URL=` est vide ou si l’adresse
ne commence pas par `http://` ou `https://`, KioskLite affiche une page
de configuration locale.

Si l’adresse est valide mais que le site est inaccessible en raison d’un problème
de réseau, de DNS ou de serveur, Midori affiche sa page habituelle d’erreur réseau.

### Remarque pour Windows

Après l’insertion d’une carte SD KioskLite dans un PC Windows, Windows peut afficher :

> There's a problem with this drive. Scan the drive now and fix it.

Ce message indique qu’un problème a été détecté sur le lecteur et propose de l’analyser et de le réparer.
Vous pouvez l’ignorer lorsque vous accédez à la partition `bootfs` pour modifier
`kiosklite.conf`.

Si Windows propose de **formater** une autre partition de la carte SD, ne la
formatez pas. KioskLite contient également un système de fichiers Linux que
Windows ne peut pas lire nativement.

## Fonctionnalités du client Raspberry Pi

Le client Raspberry Pi de KioskLite propose :

- une base légère Raspberry Pi OS Bookworm 32 bits
- un environnement graphique minimal Xorg
- le gestionnaire de fenêtres Openbox
- le navigateur Midori en plein écran
- le redémarrage automatique du navigateur après sa fermeture ou un plantage
- la désactivation de la mise en veille de l’écran et de sa gestion de l’alimentation
- le masquage automatique du pointeur de la souris
- la configuration de l’URL depuis la partition `bootfs`
- une configuration modifiable depuis Windows
- une page de configuration locale en cas d’URL absente ou invalide
- la prise en charge des personnalisations de Raspberry Pi Imager au premier démarrage
- un journal systemd conservé uniquement en mémoire dans l’image publiée

KioskLite peut afficher n’importe quel site HTTP ou HTTPS. L’application web
KioskLite est facultative.

## Application web facultative

Le dépôt contient également une application PHP légère d’affichage dynamique
dans le répertoire `web/`.

Elle propose :

- deux zones de diaporama indépendantes
- la prise en charge des images et des vidéos
- une durée d’affichage configurable pour les images
- le classement des médias dans l’ordre souhaité
- l’activation et la désactivation des médias
- des dates de début et de fin de publication
- l’agrandissement automatique d’une zone lorsqu’un seul côté contient des médias actifs
- un titre de borne configurable
- l’affichage facultatif de la date
- l’affichage facultatif de la météo avec Open-Meteo
- une interface d’administration accessible depuis un navigateur
- l’envoi et la suppression de médias
- une interface disponible en anglais et en français

Aucune base de données n’est nécessaire.

## Matériel testé

KioskLite a été testé sur :

- Raspberry Pi 2 Model B — fonctionnel
- Raspberry Pi 3 — fonctionnel
- Raspberry Pi Zero — diaporamas d’images testés avec les corrections ci-dessus ; vidéos déconseillées

L’image prête à l’emploi de KioskLite v1.1.0 a été validée sur un
Raspberry Pi 2 Model B, y compris lors d’un premier démarrage complet
avec les personnalisations de Raspberry Pi Imager.

Le Wi-Fi nécessite un Raspberry Pi avec Wi-Fi intégré ou un adaptateur
Wi-Fi USB compatible. Une connexion Ethernet peut être utilisée sur
les modèles dépourvus de matériel Wi-Fi.

Pour plus de détails, consultez :

[Compatibilité matérielle](docs/hardware.md)

## Structure du dépôt

```text
KioskLite/
├── raspberry/
│   ├── kiosklite/
│   │   ├── config-error.html
│   │   ├── kiosklite.conf
│   │   └── xinitrc
│   └── xinitrc.example
│
├── web/
│   ├── index.php
│   ├── admin.php
│   ├── api.php
│   ├── i18n.php
│   ├── config.local.example.php
│   ├── lang/
│   │   ├── en.php
│   │   └── fr.php
│   └── media/
│       ├── left/
│       │   └── .gitkeep
│       └── right/
│           └── .gitkeep
│
├── docs/
│   ├── installation.md
│   ├── configuration.md
│   ├── hardware.md
│   └── backup-pishrink.md
│
├── CHANGELOG.md
├── README.md
├── README.fr.md
├── LICENSE
├── .gitignore
└── .gitattributes
```

## Installation

La méthode la plus simple pour installer KioskLite consiste à utiliser
l’image disque prête à l’emploi avec Raspberry Pi Imager.

Après avoir écrit l’image sur une carte SD, il suffit de modifier
`kiosklite.conf` sur la partition `bootfs` pour configurer le site
web affiché.

Aucune configuration manuelle de Linux n’est nécessaire avec l’image publiée.

KioskLite peut également être installé manuellement sur Raspberry Pi OS Lite
avec Xorg, Openbox et Midori.

Pour l’installation manuelle et les détails techniques, consultez :

[Guide d’installation](docs/installation.md)

L’application web facultative peut être déployée sur n’importe quel
serveur web prenant en charge PHP.

Pour le déploiement et la configuration du serveur web, consultez :

[Guide de configuration web](docs/configuration.md)

## Configuration

### Client Raspberry Pi

Le site web affiché par KioskLite se configure dans :

`/boot/firmware/kiosklite.conf`

Ce même fichier est directement accessible sur la partition `bootfs` lorsque
la carte SD est insérée dans un PC Windows.

Exemple :

`URL=https://www.example.com`

Les lignes commençant par `#` sont des commentaires.

L’URL doit commencer par `http://` ou `https://`.

Les fichiers de configuration modifiés sous Windows sont pris en charge.

### Application web facultative

Les fichiers de configuration locale de l’application web sont volontairement
exclus de Git.

Le dépôt contient :

`web/config.local.example.php`

Copiez ce fichier vers :

`web/config.local.php`

puis définissez votre mot de passe administrateur local.

La configuration utilisée en fonctionnement et les médias envoyés sont également
exclus du suivi de versions.

Pour des instructions détaillées sur la configuration de l’application web, consultez :

[Guide de configuration](docs/configuration.md)

## Langues

KioskLite intègre un système simple d’internationalisation.

Langues actuellement disponibles :

- anglais
- français

Les fichiers de langue sont stockés dans :

    web/lang/

La langue peut être choisie directement depuis l’interface d’administration.

La langue sélectionnée est enregistrée dans la session utilisateur et dans un cookie du navigateur.

Pour ajouter une langue, créez un nouveau fichier de traduction à partir de :

    web/lang/en.php

Les clés de traduction doivent rester inchangées ; seules les valeurs traduites doivent être modifiées.

## Médias

Les fichiers multimédias utilisés par l’application sont stockés dans :

```text
web/media/left/
web/media/right/
```

Seuls les fichiers `.gitkeep` sont suivis par Git.

Les images et vidéos envoyées depuis l’interface d’administration ne sont donc pas incluses accidentellement dans les commits.

## Client Raspberry Pi

Le Raspberry Pi démarre automatiquement une session X minimale et lance
Midori en plein écran.

Dans KioskLite v1.1.0, l’URL du navigateur est lue depuis
`/boot/firmware/kiosklite.conf` au lieu d’être inscrite directement dans
`.xinitrc`.

La configuration de démarrage de X fournie relance automatiquement Midori
si le navigateur se ferme de manière inattendue. La borne peut ainsi
reprendre son fonctionnement sans redémarrer le Raspberry Pi.

Les fichiers exacts utilisés dans l’image de la version v1.1.0 sont disponibles dans :

`raspberry/kiosklite/`

Il s’agit de :

- `xinitrc`
- `kiosklite.conf`
- `config-error.html`

L’ancien fichier `raspberry/xinitrc.example` est conservé comme référence
simple pour les installations manuelles.

## Versions publiées

Consultez [CHANGELOG.md](CHANGELOG.md) pour l’historique des versions.

### KioskLite v1.1.0

Date de publication : **2026-09-16**

Image :

`KioskLite-v1.1.0.img`

Taille de l’image :

`5830103552 bytes`

SHA-256 :

`7f13886c194eea58ecb935a2fba2b3829b58f592ea3342600ee8aa78cf267b37`

Vérifiez toujours la somme de contrôle d’une image téléchargée avant
de l’écrire sur une carte SD.

## État du projet

KioskLite est en cours de développement actif.

La version 1.1.0 fournit un client de borne web Raspberry Pi simple et testé,
dont l’URL peut être configurée directement depuis la partition de démarrage
de la carte SD.

L’application PHP d’affichage dynamique reste disponible comme serveur
de contenu facultatif.

Les prochaines versions pourront proposer des fonctionnalités supplémentaires
et d’autres implémentations du serveur.

## Licence

Consultez le fichier [LICENSE](LICENSE).
