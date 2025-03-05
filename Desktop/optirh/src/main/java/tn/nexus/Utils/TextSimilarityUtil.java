package tn.nexus.Utils;

import java.util.Arrays;
import java.util.Set;
import java.util.stream.Collectors;

public class TextSimilarityUtil {

    // Liste de mots-clés techniques à rechercher (exemple)
    private static final Set<String> TECHNICAL_KEYWORDS = Set.of(
            "java", "spring", "docker", "angular", "python", "sql", "react"
    );

    public static double calculateMatchPercentage(String cvText, String jobDescription) {
        // 1. Nettoyage basique des textes
        Set<String> cvWords = cleanAndTokenize(cvText);
        Set<String> jobWords = cleanAndTokenize(jobDescription);

        // 2. Compter les mots-clés correspondants
        long matchingKeywords = cvWords.stream()
                .filter(TECHNICAL_KEYWORDS::contains)
                .filter(jobWords::contains)
                .count();

        // 3. Calcul du pourcentage
        double maxPossible = Math.max(TECHNICAL_KEYWORDS.size(), 1); // Éviter division par zéro
        return (matchingKeywords / maxPossible) * 100;
    }

    private static Set<String> cleanAndTokenize(String text) {
        return Arrays.stream(text.toLowerCase().split("[^a-zA-Z0-9]"))
                .filter(word -> !word.isEmpty())
                .collect(Collectors.toSet());
    }
}