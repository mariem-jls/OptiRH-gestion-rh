package tn.nexus.Entities.transport;

import java.sql.PreparedStatement;
import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.List;


public class Trajet {

        private int id;
        private String type;
        private String station;
        private String depart;
        private String arrive;

        public Trajet() {
        }

        public Trajet(int id, String type, String station, String depart, String arrive) {
            this.id = id;
            this.type = type;
            this.station = station;
            this.depart = depart;
            this.arrive = arrive;
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

        @Override
        public String toString() {
            return "Reservation_Evenement{" +
                    "id=" + id +
                    ", type=" + type +
                    ", station=" + station +
                    ", depart=" + depart +
                    ", arrive=" + arrive +
                    '}';
        }



}
