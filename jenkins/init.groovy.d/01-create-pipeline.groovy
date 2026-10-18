import jenkins.model.Jenkins
import org.jenkinsci.plugins.workflow.job.WorkflowJob
import org.jenkinsci.plugins.workflow.cps.CpsFlowDefinition

def jenkins = Jenkins.instance
def name = 'nutritrace-ci-cd'

if (jenkins.getItem(name) != null) {
    println "Job ${name} existe déjà — skip"
    return
}

def jenkinsfile = new File('/workspace/nutritrace/Jenkinsfile')
if (!jenkinsfile.exists()) {
    println "Jenkinsfile introuvable — job non créé"
    return
}

def job = jenkins.createProject(WorkflowJob, name)
job.setDescription('Pipeline CI/CD NutriTrace (Jenkinsfile monté depuis Docker)')
job.setDefinition(new CpsFlowDefinition(jenkinsfile.getText('UTF-8'), true))
job.save()

println "Job ${name} créé avec succès"
