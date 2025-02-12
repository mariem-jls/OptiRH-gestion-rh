package tn.nexus.Entities;

public class Vehicule {

    private int id;
    private String disponibilite;
    private String type;
    private int nbrplace;

    public Vehicule() {
    }

    public Vehicule(int id, String disponibilite, String type, int nbrplace) {
        this.id = id;
        this.disponibilite = disponibilite;
        this.type = type;
        this.nbrplace = nbrplace;
    }

    public int getId() {
        return id;
    }

    public void setId(int id) {
        this.id = id;
    }

    public String getdesponibilite() {
        return disponibilite;
    }

    public void setdesponibilite(String disponibilite) {
        this.disponibilite = disponibilite;
    }

    public String gettype() {
        return type;
    }

    public void settype(String type) {
        this.type = type;
    }

    public int getnbrplace() {
        return nbrplace;
    }

    public void setnbrplace(int nbrplace) {
        this.nbrplace = nbrplace;
    }

    @Override
    public String toString() {
        return "Reservation_Evenement{" +
                "id=" + id +
                ", disponibilite=" + disponibilite +
                ", type=" + type +
                ", nbrplace=" + nbrplace +
                '}';
    }
}
