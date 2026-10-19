# Guide DevOps — Mise en place pour l’équipe NutriTrace

Ce guide explique **depuis zéro** comment récupérer le projet et activer la partie **DevOps** (Docker + Jenkins CI/CD) sur ta machine.

Document théorique / livrable enseignant : [`DevOps-NutriTrace.md`](DevOps-NutriTrace.md)

---

## 0. Ce que tu vas obtenir

| Service | URL | Rôle |
|---------|-----|------|
| App NutriTrace (Docker) | http://127.0.0.1:8088 | Application déployée (CD) |
| Jenkins | http://127.0.0.1:8090 | Pipelines CI + CD |
| MySQL (conteneur) | `localhost:3307` | Base de l’app Docker |

**Deux jobs Jenkins :**

| Job | Fichier | Rôle |
|-----|---------|------|
| **CI** | `ci/Jenkinsfile` | Composer, NPM, Vite, tests |
| **CD** | `cd/Jenkinsfile` | Build image Docker + déploiement |

Après un CI **vert**, le CD se lance tout seul.

---

## 1. Prérequis (à installer une seule fois)

1. **Git**
2. **Docker Desktop** (Windows) — doit être **démarré** (icône baleine dans la barre des tâches)
3. Compte GitHub avec accès au dépôt EspritHub  
   `https://github.com/medamin87737/Esprit_5Twin6_EspritHub.git`
4. (Optionnel pour développer hors Docker) **PHP 8.2**, **Composer**, **Node.js 22**

Vérifie Docker :

```powershell
docker version
docker compose version
```

---

## 2. Récupérer le projet (pull / clone)

### Première fois (clone)

```powershell
cd $env:USERPROFILE\Desktop
git clone https://github.com/medamin87737/Esprit_5Twin6_EspritHub.git nutritrace
cd nutritrace
```

### Déjà cloné (pull)

```powershell
cd chemin\vers\ton\projet
git pull origin main
```

> Les fichiers DevOps à connaître :
> - `docker-compose.yml` — app + db + jenkins  
> - `Dockerfile` — image de l’application  
> - `Dockerfile.jenkins` — image Jenkins  
> - `ci/Jenkinsfile` — pipeline CI  
> - `cd/Jenkinsfile` — pipeline CD  

---

## 3. Adapter le chemin Windows (OBLIGATOIRE)

Dans `docker-compose.yml`, service `jenkins`, variable **`HOST_PROJECT_DIR`** :

```yaml
HOST_PROJECT_DIR: //c/Users/amin/Desktop/laravelProject
```

**Remplace** par le chemin de **ton** dossier projet, au format Docker Desktop :

| Chemin Windows réel | Valeur `HOST_PROJECT_DIR` |
|---------------------|---------------------------|
| `C:\Users\sara\Desktop\nutritrace` | `//c/Users/sara/Desktop/nutritrace` |
| `C:\Users\ali\Documents\EspritHub` | `//c/Users/ali/Documents/EspritHub` |

Règle : `C:\Users\...` → `//c/Users/...` (slashes `/`, minuscule `c`).

Sans ça, le job **CI** (volumes `docker run -v`) échoue.

---

## 4. Lancer Docker (app + base + Jenkins)

Dans le dossier du projet :

```powershell
docker compose up -d --build
```

Première fois : **plusieurs minutes** (build Jenkins + app).

Vérifie :

```powershell
docker compose ps
```

Tu dois voir `nutritrace-app`, `nutritrace-db`, `nutritrace-jenkins` en **Up**.

| Test | Action |
|------|--------|
| App | Ouvre http://127.0.0.1:8088 |
| Jenkins | Ouvre http://127.0.0.1:8090 |

### Mot de passe initial Jenkins

```powershell
docker exec nutritrace-jenkins cat /var/jenkins_home/secrets/initialAdminPassword
```

Copie le mot de passe → colle-le dans le setup wizard Jenkins → installe les plugins proposés (ou « Install suggested ») → crée un utilisateur admin.

---

## 5. Configurer les 2 jobs Jenkins (une seule fois)

### 5.1 Job **CI**

1. Jenkins → **New Item**
2. Nom exact : **`CI`** (important : le CD est déclenché avec ce nom)
3. Type : **Pipeline** → OK
4. Onglet **Pipeline** :
   - Definition : **Pipeline script from SCM**
   - SCM : **Git**
   - Repository URL :  
     `https://github.com/medamin87737/Esprit_5Twin6_EspritHub.git`
   - Branch : `*/main`
   - **Script Path** : `ci/Jenkinsfile`
5. **Décoche** « Lightweight checkout » (sinon erreur Git / workspace)
6. Save → **Build Now**

### 5.2 Job **CD**

1. **New Item** → nom exact : **`CD`**
2. Type : **Pipeline**
3. Même SCM Git / branche `main`
4. **Script Path** : `cd/Jenkinsfile`
5. **Décoche** « Lightweight checkout »
6. Save

> Le CD peut être lancé à la main (**Build Now**), ou **automatiquement** quand le CI réussit.

### 5.3 Credentials GitHub (si le dépôt est privé)

- Jenkins → Manage Jenkins → Credentials  
- Ajoute un token GitHub (PAT)  
- Dans chaque job SCM, sélectionne ces credentials  

Dépôt public : pas obligatoire.

---

## 6. Vérifier que tout marche

### CI

1. Ouvre le job **CI** → **Build Now**
2. Console Output / Stage View : stages en vert  
   `1. Recuperation du code` → deps → Vite → qualité → tests

### CD

1. Soit attendre la fin d’un CI vert (lancement auto)  
2. Soit **Build Now** sur **CD**
3. Stages : build image → déploiement → test HTTP  
4. App à jour : http://127.0.0.1:8088

### Déclenchement auto après un push

1. Tu pousses sur `main` (ou merge une PR)
2. CI poll Git toutes les ~2 min (`pollSCM`)
3. CI vert → CD démarre

---

## 7. Développement local (hors Jenkins) — rappel

Pour coder / tester sans passer par Docker app :

```powershell
composer install
npm install
copy .env.example .env   # si besoin
php artisan key:generate
composer run dev         # serve + queue + vite
```

App locale typique : http://127.0.0.1:8000  

L’app **Docker / CD** reste sur **:8088** — les deux peuvent coexister.

### E-mails (Gmail uniquement)

NutriTrace envoie les mails **uniquement via SMTP Gmail** (plus de Resend).

Dans ton `.env` (ne pas committer) :

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=ton.email@gmail.com
MAIL_PASSWORD=xxxx xxxx xxxx xxxx
MAIL_FROM_ADDRESS="ton.email@gmail.com"
```

`MAIL_PASSWORD` = [mot de passe d’application Google](https://myaccount.google.com/apppasswords) (16 caractères), **pas** le mot de passe du compte.

Après modification :

```powershell
php artisan config:clear
```

Puis relancer `composer run dev`.  
Si l’équipe partage le même compte de test : chacun doit quand même faire `config:clear` après avoir collé le `.env`.

---

## 8. Schéma rapide du flux

```
  Membre équipe
       │  git push main
       ▼
  GitHub EspritHub
       │  poll SCM (~2 min)
       ▼
  Jenkins job CI  ──échec──► stop
       │ succès
       ▼
  Jenkins job CD
       │  docker build + compose
       ▼
  http://127.0.0.1:8088
```

---

## 9. Problèmes fréquents

| Symptôme | Cause probable | Solution |
|----------|----------------|----------|
| `permission denied` sur `docker.sock` | Jenkins pas en root | Ne pas changer `user: root` dans compose |
| `path "//c/Users/..." not found` au **build** | Ancien CD | Pull le dernier `cd/Jenkinsfile` (build depuis `.`) |
| CI : volume vide / fichiers manquants | Mauvais `HOST_PROJECT_DIR` | Corriger le chemin §3 puis `docker compose up -d jenkins` |
| `Lightweight checkout` / not a git directory | Option cochée | Décocher dans les 2 jobs |
| Job CD jamais lancé | Nom du job ≠ `CD` | Renommer le job en **`CD`** |
| Port 8088 / 8090 déjà pris | Autre conteneur | `docker ps` puis arrêter le conflit |
| `concurrently is not recognized` | `node_modules` cassé (install Docker) | Sur Windows : `npm install` dans le projet |
| curl CD = 500 Vite manifest | Image sans assets | Pull le `Dockerfile` multi-stage (npm build inclus) |

Redémarrer Jenkins après changement de compose :

```powershell
docker compose up -d --build jenkins
```

Voir les logs :

```powershell
docker logs nutritrace-jenkins --tail 50
docker logs nutritrace-app --tail 50
```

---

## 10. Checklist « je suis prêt »

- [ ] Docker Desktop démarré  
- [ ] `git pull` à jour  
- [ ] `HOST_PROJECT_DIR` adapté à **mon** PC  
- [ ] `docker compose up -d --build` OK  
- [ ] http://127.0.0.1:8088 répond  
- [ ] http://127.0.0.1:8090 = Jenkins configuré  
- [ ] Jobs nommés exactement **`CI`** et **`CD`**  
- [ ] Script Path = `ci/Jenkinsfile` et `cd/Jenkinsfile`  
- [ ] Lightweight checkout **décoché**  
- [ ] Build CI vert → CD vert  

---

## 11. Qui contacter

| Sujet | Personne |
|-------|----------|
| DevOps / Jenkins / Docker | Amin (Module 2 + livrable DevOps) |
| Comparateur fiches `/produits/comparer` | Ghada (Module 1) |
| Comparateur empreintes `/comparer` + Climatiq | Sahar (Module 4) |

Bon setup à toute l’équipe.
