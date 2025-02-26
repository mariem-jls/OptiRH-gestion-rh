package tn.nexus.Entities.Evenement;

public enum StatusEvenement {

    A_VENIR("à venir"),
    EN_COURS("en cours"),
    TERMINE("terminé");

    private final String label;

    StatusEvenement(String label) {
        this.label = label;
    }

    public String getLabel() {
        return label;
    }

    public static StatusEvenement fromString(String text) {
        for (StatusEvenement s : StatusEvenement.values()) {
            if (s.label.equalsIgnoreCase(text)) {
                return s;
            }
        }
        throw new IllegalArgumentException("Statut inconnu : " + text);
    }
}
