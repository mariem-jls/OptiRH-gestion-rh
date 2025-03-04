package tn.nexus.Controllers.Mission;
import com.itextpdf.text.*;
import com.itextpdf.text.pdf.PdfPCell;
import com.itextpdf.text.pdf.PdfPTable;
import tn.nexus.Entities.Mission.Mission;
import com.itextpdf.text.pdf.PdfWriter;
import tn.nexus.Entities.Mission.Projet;

import java.io.FileOutputStream;
import java.io.IOException;
import java.util.List;
import java.util.stream.Stream;

public class PDFExporter {

    public static void exportMissionsToPDF(List<Mission> missions, String filePath) {
        Document document = new Document();

        try {
            // Créer un fichier PDF
            PdfWriter.getInstance(document, new FileOutputStream(filePath));
            document.open();

            // Ajouter un titre
            document.add(new Paragraph("Liste des missions"));

            // Ajouter chaque mission au PDF
            for (Mission mission : missions) {
                document.add(new Paragraph("Titre: " + mission.getTitre()));
                document.add(new Paragraph("Description: " + mission.getDescription()));
                document.add(new Paragraph("Statut: " + mission.getStatus()));
                document.add(new Paragraph("Date de début: " + mission.getCreatedAt()));
                document.add(new Paragraph("Date de fin: " + mission.getDateTerminer()));
                document.add(new Paragraph("----------------------------------------"));
            }

            document.close();
        } catch (DocumentException | IOException e) {
            e.printStackTrace();
        }
    }
    public static void exportProjectsToPDF(List<Projet> projets, String filePath) throws Exception {
        Document document = new Document();
        PdfWriter.getInstance(document, new FileOutputStream(filePath));

        document.open();

        // Titre
        Font titleFont = FontFactory.getFont(FontFactory.HELVETICA_BOLD, 18);
        Paragraph title = new Paragraph("Liste des Projets", titleFont);
        title.setAlignment(Element.ALIGN_CENTER);
        document.add(title);

        // Espacement
        document.add(new Paragraph(" "));

        // Tableau des projets
        PdfPTable table = new PdfPTable(5);
        table.setWidthPercentage(100);

        // En-têtes
        Stream.of("ID", "Nom", "Description", "Créé par", "Date création")
                .forEach(header -> {
                    PdfPCell cell = new PdfPCell();
                    cell.setBackgroundColor(BaseColor.LIGHT_GRAY);
                    cell.setPhrase(new Phrase(header));
                    table.addCell(cell);
                });

        // Données
        for (Projet projet : projets) {
            table.addCell(String.valueOf(projet.getId()));
            table.addCell(projet.getNom());
            table.addCell(projet.getDescription());
            table.addCell(projet.getUserNom());
            table.addCell(projet.getCreatedAt().toString());
        }

        document.add(table);
        document.close();
    }
}