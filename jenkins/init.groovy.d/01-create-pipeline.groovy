import jenkins.model.Jenkins
import org.jenkinsci.plugins.workflow.job.WorkflowJob
import org.jenkinsci.plugins.workflow.cps.CpsFlowDefinition

def jenkins = Jenkins.instance

def createFromFile = { String name, String path, String description ->
    if (jenkins.getItem(name) != null) {
        println "Job ${name} existe déjà — skip"
        return
    }
    def file = new File(path)
    if (!file.exists()) {
        println "Fichier introuvable: ${path} — job ${name} non créé"
        return
    }
    def job = jenkins.createProject(WorkflowJob, name)
    job.setDescription(description)
    job.setDefinition(new CpsFlowDefinition(file.getText('UTF-8'), true))
    job.save()
    println "Job ${name} créé (${path})"
}

createFromFile(
    'nutritrace-ci',
    '/workspace/nutritrace/ci/Jenkinsfile',
    'CI seul — dépendances, build assets, tests (ci/Jenkinsfile)'
)

createFromFile(
    'nutritrace-cd',
    '/workspace/nutritrace/cd/Jenkinsfile',
    'CD seul — build image Docker + déploiement Compose (cd/Jenkinsfile)'
)
