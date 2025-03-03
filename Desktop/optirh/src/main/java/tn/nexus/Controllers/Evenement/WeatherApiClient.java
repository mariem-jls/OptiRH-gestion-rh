package tn.nexus.Controllers.Evenement;

import okhttp3.OkHttpClient;
import okhttp3.Request;
import okhttp3.Response;
import java.io.IOException;

public class WeatherApiClient {
    private static final String API_KEY = "651c995b5567be56017021a628903cff";
    private static final String BASE_URL = "https://api.openweathermap.org/data/2.5/forecast";

    public String getWeatherData(double lat, double lon, String date) {
        OkHttpClient client = new OkHttpClient();

        String url = String.format("%s?lat=%.4f&lon=%.4f&appid=%s&units=metric&lang=fr",
                BASE_URL, lat, lon, API_KEY);

        Request request = new Request.Builder()
                .url(url)
                .build();

        try (Response response = client.newCall(request).execute()) {
            if (response.isSuccessful() && response.body() != null) {
                return response.body().string();
            } else {
                return null;
            }
        } catch (IOException e) {
            e.printStackTrace();
            return null;
        }
    }
}





