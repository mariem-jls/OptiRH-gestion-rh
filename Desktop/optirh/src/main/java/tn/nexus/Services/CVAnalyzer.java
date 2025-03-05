package tn.nexus.Services;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.fasterxml.jackson.databind.node.ArrayNode;
import com.fasterxml.jackson.databind.node.ObjectNode;
import org.apache.http.client.methods.CloseableHttpResponse;
import org.apache.http.client.methods.HttpPost;
import org.apache.http.entity.ContentType;
import org.apache.http.entity.mime.MultipartEntityBuilder;
import org.apache.http.impl.client.CloseableHttpClient;
import org.apache.http.impl.client.HttpClients;
import org.apache.http.util.EntityUtils;
import tn.nexus.Entities.Recrutement.ResultatAnalyse;

import java.io.File;
import java.io.IOException;
import java.nio.charset.StandardCharsets;
import java.time.LocalDate;
import java.time.format.DateTimeParseException;
import java.util.*;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

public class CVAnalyzer {
    private static final String API_KEY = "aff_f9eae115b2de640ccebea2960bda9213a12314d4";
    private static final String API_URL = "https://api.affinda.com/v2/resumes";
    private static final ObjectMapper mapper = new ObjectMapper();

    private static final Set<String> VALID_SKILLS = Set.of(
            "Java", "Python", "Spring Boot", "Docker",
            "React", "Node.js", "SQL", "JavaScript"
    );


    public static void analyzeCV(String filePath) throws IOException {
        File file = new File(filePath);
        String rawJson = sendRequest(file);
        JsonNode response = mapper.readTree(rawJson);

        // Nouvelle vérification basée sur le code HTTP
        int httpStatus = response.path("statusCode").asInt(500);
        if (httpStatus >= 400) {
            handleError(response);
            return;
        }

        // Meilleure vérification de la structure d'erreur
        if (response.has("error") && !response.get("error").isEmpty()) {
            handleError(response);
            return;
        }

        if (response.has("data")) {
            JsonNode data = response.get("data");

            // Correction centralisée des bugs connus
            fixKnownAffindaBugs(data);

            // Nouvelle validation améliorée
            if (!validateData(data)) {
                System.err.println("Données invalides - traitement interrompu");
                return;
            }

            cleanData(data);
            printResults(data);
        } else {
            System.out.println("Aucune donnée analysable - format non supporté ?");
        }
    }

    private static String sendRequest(File file) throws IOException {
        try (CloseableHttpClient httpClient = HttpClients.createDefault()) {
            HttpPost request = new HttpPost(API_URL);
            request.setHeader("Authorization", "Bearer " + API_KEY);

            System.out.println("Envoi du fichier : " + file.getAbsolutePath());
            System.out.println("Taille du fichier : " + file.length() + " bytes");

            MultipartEntityBuilder builder = MultipartEntityBuilder.create();
            builder.addBinaryBody("file", file, ContentType.create("application/pdf"), file.getName());
            request.setEntity(builder.build());

            try (CloseableHttpResponse response = httpClient.execute(request)) {
                int statusCode = response.getStatusLine().getStatusCode();
                String rawResponse = EntityUtils.toString(response.getEntity());

                System.out.println("Code HTTP : " + statusCode);
                byte[] bytes = rawResponse.getBytes(StandardCharsets.ISO_8859_1);
                String utf8Response = new String(bytes, StandardCharsets.UTF_8);
                System.out.println(utf8Response);

               // System.out.println("Réponse brute :\n" + rawResponse);


                return rawResponse;
            }
        }
    }

    private static void cleanData(JsonNode data) {
        // Nettoyage des compétences amélioré
        cleanSkills(data);

        // Correction LinkedIn plus robuste
        if (data.has("linkedin") && data instanceof ObjectNode) {
            ObjectNode objectData = (ObjectNode) data;
            String linkedin = data.path("linkedin").asText("")
                    .replaceAll("\\s+", "") // Suppression de tous les espaces
                    .replaceFirst("^(?!https?://)", "https://"); // Ajout du protocole si manquant

            objectData.put("linkedin", linkedin);
        }
    }
    private static void cleanSkills(JsonNode data) {
        if (data.has("skills") && data.get("skills").isArray()) {
            ArrayNode skills = (ArrayNode) data.get("skills");
            List<JsonNode> validSkills = new ArrayList<>();

            skills.forEach(skill -> {
                String skillName = skill.path("name").asText("").trim();
                if (isValidSkill(skillName)) {
                    validSkills.add(skill);
                }
            });

            // Réinitialisation avec les compétences valides
            skills.removeAll().addAll(validSkills);
        }
    }
    private static boolean isValidSkill(String skillName) {
        // Vérification insensible à la casse et avec gestion partielle
        return VALID_SKILLS.stream()
                .anyMatch(valid -> skillName.equalsIgnoreCase(valid));
    }


    // Validation renforcée
    private static boolean validateData(JsonNode data) {
        boolean isValid = true;

        // Validation date de naissance
        if (data.has("dateOfBirth")) {
            try {
                LocalDate birthDate = LocalDate.parse(data.path("dateOfBirth").asText());
                if (birthDate.isBefore(LocalDate.now().minusYears(70)) ||
                        birthDate.isAfter(LocalDate.now().minusYears(14))) {
                    System.err.println("[ERREUR] Date de naissance invalide: " + birthDate);
                    ((ObjectNode) data).remove("dateOfBirth");
                    isValid = false;
                }
            } catch (DateTimeParseException e) {
                System.err.println("[ERREUR] Format de date illisible");
                isValid = false;
            }
        }

        return isValid;
    }

    private static List<String> extractMainTechnologies(JsonNode data) {
        List<String> technologies = new ArrayList<>();
        if (data.has("skills")) {
            data.get("skills").forEach(skill ->
                    technologies.add(skill.path("name").asText())
            );
        }
        return technologies;
    }

    private static void printResults(JsonNode data) {
        System.out.println("\n=== RÉSULTATS STRUCTURÉS ===");

        // Nom
        String nom = data.path("name").path("raw").asText("Non spécifié");
        System.out.println("Nom: " + nom);

        // Expérience
        // Dans printResults(), gérer le cas où l'expérience n'est pas détectée
        int experience = data.path("totalYearsExperience").asInt(0);
        if (experience == 0) {
            // Extraire l'expérience depuis le texte brut
            String summary = data.path("summary").asText("");
            Matcher m = Pattern.compile("\\d+ ans").matcher(summary);
            if (m.find()) experience = Integer.parseInt(m.group().replace(" ans", ""));
        }
        System.out.println("Expérience totale: " + experience + " ans");

        // Technologies
        List<String> technologies = extractMainTechnologies(data);
        System.out.println("Technologies clés: " + String.join(", ", technologies));

        // LinkedIn
        String linkedin = data.path("linkedin").asText("Non renseigné");
        System.out.println("LinkedIn: " + linkedin);
    }

    // Gestion d'erreur améliorée
    private static void handleError(JsonNode errorResponse) {
        System.err.println("=== ERREUR D'ANALYSE ===");

        // Vérifier d'abord le code HTTP
        int httpStatus = errorResponse.path("statusCode").asInt(-1);
        if (httpStatus >= 200 && httpStatus < 300) {
            System.out.println("Réponse API apparemment valide malgré la structure d'erreur");
            return;
        }

        JsonNode errorNode = errorResponse.path("error");
        if (errorNode.isMissingNode() || errorNode.isNull()) {
            System.err.println("Erreur inconnue - réponse brute :\n" + errorResponse.toPrettyString());
            return;
        }

        String code = errorNode.path("errorCode").asText("non_specifie");
        String detail = errorNode.path("errorDetail").asText("aucun_detail");

        // Filtre spécifique pour les faux positifs d'erreur
        if ("non_specifie".equals(code) && errorResponse.has("data")) {
            System.out.println("Analyse partielle réussie");
            printResults(errorResponse.get("data")); // Afficher malgré l'erreur
            return;
        }

        System.err.println("Code erreur : " + code);
        System.err.println("Détails techniques : " + detail);

        // Ajouter une aide contextuelle
        if (detail.contains("S3")) {
            System.err.println("\nCONSEIL : Le fichier semble être un PDF scanné/invalide");
            System.err.println("→ Convertir le PDF en texte avec Adobe Acrobat ou https://pdftotext.com");
        }
    }
    private static void fixKnownAffindaBugs(JsonNode data) {
        // Correction du bug de date de naissance aléatoire
        if (data.has("dateOfBirth")) {
            String dob = data.get("dateOfBirth").asText();
            if (dob.startsWith("1933") || dob.startsWith("1900")) {
                ((ObjectNode) data).remove("dateOfBirth");
            }
        }

        // Correction des URLs tronquées
        if (data.has("linkedin")) {
            String linkedin = data.get("linkedin").asText();
            if (linkedin.contains("linkedin.com/in/")) {
                ((ObjectNode) data).put("linkedin", linkedin.replace(" ", ""));
            }
        }
    }
    public static Map<String, JsonNode> analyzeMultipleCVs(List<String> filePaths) throws IOException {
        Map<String, JsonNode> results = new HashMap<>();

        for (String filePath : filePaths) {
            File file = new File(filePath);
            if (!file.exists() || file.length() == 0) {
                System.err.println("Fichier introuvable ou vide : " + filePath);
                continue; // Passer au fichier suivant
            }

            String rawJson = sendRequest(file);
            JsonNode response = mapper.readTree(rawJson);

            if (response.has("data")) {
                JsonNode data = response.get("data");
                cleanData(data);
                results.put(filePath, data); // Stocker les résultats du CV
            } else {
                System.err.println("Aucune donnée analysable pour : " + filePath);
            }
        }

        return results; // Retourne les analyses des CV
    }



        public static ResultatAnalyse analyserCV(String filePath) throws IOException {
            File file = new File(filePath);
            String rawJson = sendRequest(file);
            JsonNode response = mapper.readTree(rawJson);

            if (response.has("error")) {
                throw new IOException("Erreur API: " + response.get("error").toString());
            }

            JsonNode data = response.get("data");
            cleanData(data);

            return mapToResultatAnalyse(data);
        }

    private static ResultatAnalyse mapToResultatAnalyse(JsonNode data) {
        String nom = data.path("name").path("raw").asText("Inconnu");
        int experience = data.path("totalYearsExperience").asInt(0);
        List<String> technologies = extractMainTechnologies(data);
        String linkedin = data.path("linkedin").asText("");

        // Calculer ou définir le pourcentage de correspondance (exemple fixe)
        String matchPercentage = "0.00%"; // À remplacer par le vrai calcul si disponible

        return new ResultatAnalyse(
                nom,
                String.valueOf(experience) + " ans",
                String.join(", ", technologies),
                linkedin,
                "0.00%"        );
    }
  /*  public static void main(String[] args) {
        List<String> filePaths = List.of(
                "C:\\Users\\jlass\\OneDrive\\Desktop\\cv11.pdf",
                "C:\\Users\\jlass\\OneDrive\\Desktop\\cv12.pdf"
        );

        try {
            Map<String, JsonNode> analyzedCVs = analyzeMultipleCVs(filePaths);

            for (Map.Entry<String, JsonNode> entry : analyzedCVs.entrySet()) {
                System.out.println("\nRésultats pour : " + entry.getKey());
                printResults(entry.getValue());
            }
        } catch (IOException e) {
            System.err.println("Erreur lors de l'analyse des CV : " + e.getMessage());
        }
    }*/

}
