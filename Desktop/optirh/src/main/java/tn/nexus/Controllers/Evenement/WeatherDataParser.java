package tn.nexus.Controllers.Evenement;

import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;

public class WeatherDataParser {
    public static String parseWeatherData(String jsonData, String date) {
        ObjectMapper mapper = new ObjectMapper();
        try {
            JsonNode root = mapper.readTree(jsonData);
            JsonNode list = root.path("list");

            for (JsonNode node : list) {
                String forecastDate = node.path("dt_txt").asText().split(" ")[0];
                if (forecastDate.equals(date)) {
                    String city = root.path("city").path("name").asText();
                    String country = root.path("city").path("country").asText();
                    double temp = node.path("main").path("temp").asDouble();
                    String description = node.path("weather").get(0).path("description").asText();
                    String iconCode = node.path("weather").get(0).path("icon").asText();

                    return String.format(
                            "📍 %s, %s\n🌡 Temp: %.1f°C\n🌤 Météo: %s",
                            city, country, temp, description
                    );
                }
            }
            return "❌ Aucune donnée météo pour cette date.";
        } catch (Exception e) {
            e.printStackTrace();
            return "❌ Erreur lors de l'analyse des données météo.";
        }
    }

    public static String getWeatherIcon(String iconCode) {
        // Chemin relatif vers le dossier des icônes dans resources
        String basePath = "/Evenement/images/";

        // Mappage des codes OpenWeatherMap à vos icônes locales
        switch (iconCode) {
            case "01d": // Ciel clair (jour)
            case "01n": // Ciel clair (nuit)
                return basePath + "clear.png";
            case "02d": // Peu nuageux (jour)
            case "02n": // Peu nuageux (nuit)
            case "03d": // Nuages dispersés (jour)
            case "03n": // Nuages dispersés (nuit)
            case "04d": // Très nuageux (jour)
            case "04n": // Très nuageux (nuit)
                return basePath + "cloudy.png";
            case "09d": // Pluie légère (jour)
            case "09n": // Pluie légère (nuit)
            case "10d": // Pluie (jour)
            case "10n": // Pluie (nuit)
                return basePath + "rain.png";
            case "11d": // Orage (jour)
            case "11n": // Orage (nuit)
                return basePath + "thender.png";
            case "13d": // Neige (jour)
            case "13n": // Neige (nuit)
                return basePath + "snow.jpg";
            default:
                return basePath + "reglages.png"; // Par défaut
        }
    }
}

