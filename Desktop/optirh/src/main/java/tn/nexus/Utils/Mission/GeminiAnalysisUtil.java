package tn.nexus.Utils.Mission;

import org.json.JSONArray;
import org.json.JSONObject;
import java.net.URI;
import java.net.http.HttpClient;
import java.net.http.HttpRequest;
import java.net.http.HttpResponse;
import java.time.Duration;

public class GeminiAnalysisUtil {
    private static final String API_KEY = "AIzaSyCBDzwB_RUWUaVKq7XDFxP1yq9xsyVIAxI";
    // Corrected API URL - Using v1beta for gemini-pro model
    private static final String API_URL = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent";
    private static final HttpClient client = HttpClient.newHttpClient();

    public static String generateText(String prompt) throws Exception {
        // Create request body according to current API specification
        JSONObject requestBody = new JSONObject();

        // Create the contents array with the prompt
        JSONArray contents = new JSONArray();
        JSONObject content = new JSONObject();
        JSONArray parts = new JSONArray();
        parts.put(new JSONObject().put("text", prompt));
        content.put("parts", parts);
        contents.put(content);
        requestBody.put("contents", contents);

        // Add generation configuration (optional)
        JSONObject generationConfig = new JSONObject()
                .put("temperature", 0.7)
                .put("maxOutputTokens", 2048)
                .put("topP", 0.95);
        requestBody.put("generationConfig", generationConfig);

        // Create and execute the HTTP request
        HttpRequest request = HttpRequest.newBuilder()
                .uri(URI.create(API_URL + "?key=" + API_KEY))
                .header("Content-Type", "application/json")
                .timeout(Duration.ofSeconds(60))
                .POST(HttpRequest.BodyPublishers.ofString(requestBody.toString()))
                .build();

        HttpResponse<String> response = client.send(request, HttpResponse.BodyHandlers.ofString());

        if (response.statusCode() == 200) {
            // Parse and extract the response text
            JSONObject responseJson = new JSONObject(response.body());

            if (!responseJson.has("candidates") || responseJson.getJSONArray("candidates").isEmpty()) {
                throw new Exception("Response does not contain candidates: " + response.body());
            }

            JSONObject candidate = responseJson.getJSONArray("candidates").getJSONObject(0);

            if (!candidate.has("content") ||
                    !candidate.getJSONObject("content").has("parts") ||
                    candidate.getJSONObject("content").getJSONArray("parts").isEmpty()) {
                throw new Exception("Invalid response structure: " + response.body());
            }

            return candidate.getJSONObject("content")
                    .getJSONArray("parts")
                    .getJSONObject(0)
                    .getString("text");
        } else {
            // Enhanced error handling
            String errorMsg = "API Error " + response.statusCode() + ": ";
            try {
                JSONObject errorJson = new JSONObject(response.body());
                if (errorJson.has("error")) {
                    errorMsg += errorJson.getJSONObject("error").getString("message");
                } else {
                    errorMsg += response.body();
                }
            } catch (Exception e) {
                errorMsg += response.body();
            }
            throw new Exception(errorMsg);
        }
    }
}