package tn.nexus.Entities.transport;


public class Trajet {

        private int id;
        private String type;
        private String station;
        private String depart;
        private String arrive;
        private double longitudeDepart; // Nouvel attribut
        private double latitudeDepart;  // Nouvel attribut
        private double longitudeArrivee; // Nouvel attribut
        private double latitudeArrivee;  // Nouvel attribut

        public Trajet() {
        }

        public Trajet(int id, String type, String station, String depart, String arrive, double longitudeDepart, double latitudeDepart, double longitudeArrivee, double latitudeArrivee  ) {
            this.id = id;
            this.type = type;
            this.station = station;
            this.depart = depart;
            this.arrive = arrive;
            this.longitudeDepart = longitudeDepart;
            this.latitudeDepart = latitudeDepart;
            this.longitudeArrivee = longitudeArrivee;
            this.latitudeArrivee = latitudeArrivee;
        }

        public int getId() {
            return id;
        }

        public void setId(int id) {
            this.id = id;
        }

        public String getType() {
            return type;
        }

        public void setType(String type) {
            this.type = type;
        }

        public String getStation() {
            return station;
        }

        public void setStation(String station) {
            this.station = station;
        }

        public String getDepart() { return depart; }

        public void setDepart(String depart) { this.depart = depart; }

        public String getArrive() { return arrive; }

        public void setArrive(String arrive) { this.arrive = arrive; }

    public double getLongitudeDepart() { return longitudeDepart; }
    public void setLongitudeDepart(double longitudeDepart) {
            this.longitudeDepart = longitudeDepart;
    }
    public double getLatitudeDepart() { return latitudeDepart; }
    public void setLatitudeDepart(double latitudeDepart) {
            this.latitudeDepart = latitudeDepart;
    }

    public double getLongitudeArrivee() { return longitudeArrivee; }
    public void setLongitudeArrivee(double longitudeArrivee) {
            this.longitudeArrivee = longitudeArrivee;
    }
    public double getLatitudeArrivee() { return latitudeArrivee; }
    public void setLatitudeArrivee(double latitudeArrivee) {
            this.latitudeArrivee = latitudeArrivee;
    }


        @Override
        public String toString() {
            return "Reservation_Evenement{" +
                    "id=" + id +
                    ", type=" + type +
                    ", station=" + station +
                    ", depart=" + depart +
                    ", arrive=" + arrive +
                    ", longitudeDepart=" + longitudeDepart +
                    ", latitudeDepart=" + latitudeDepart +
                    ", longitudeArrivee=" + longitudeArrivee +
                    ", latitudeArrivee=" + latitudeArrivee +
                    '}';
        }



}
