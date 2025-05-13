package projekt;

import com.impinj.octane.*;

import java.io.FileWriter;
import java.io.IOException;
import java.io.OutputStream;
import java.net.HttpURLConnection;
import java.net.URL;
import java.net.URLEncoder;
import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.Scanner;
import java.util.List;
import java.util.ArrayList;
import java.util.concurrent.*;
import java.util.Collections;
import java.util.Set;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.io.BufferedReader;
import java.nio.charset.StandardCharsets;

public class HasaTime {

    /**
     * Třída pro uchování informací o tagu, který čeká na zpracování
     * 
     * @param epc             EPC tagu
     * @param timestamp       Čas, kdy byl tag přečten
     * @param scheduledFuture Budoucí úkol, který zpracovává tag
     */
    static class PendingTag {
        String epc;
        long timestamp;
        ScheduledFuture<?> scheduledFuture;

        public PendingTag(String epc, long timestamp, ScheduledFuture<?> scheduledFuture) {
            this.epc = epc;
            this.timestamp = timestamp;
            this.scheduledFuture = scheduledFuture;
        }
    }

    // Set of EPCs that returned 208 and should be ignored
    private static final Set<String> ignoredTags = Collections.newSetFromMap(new ConcurrentHashMap<String, Boolean>());

    /**
     * Předá tag do API a v případě neúspěchu zavolá {@link #logFailedTag}
     * 
     * @param epc       EPC daného tagu
     * @param timestamp Datum a čas, kdy byl tag přečten ve formátu "yyyy-MM-dd
     *                  HH:mm:ss.SSS"
     */
    private static void processTag(String epc, long timestamp) {
        SimpleDateFormat sdf = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss.SSS");
        String formattedTime = sdf.format(new Date(timestamp));
        try {
            URL url = new URL("http://127.0.0.1:8000/tagRead");
            HttpURLConnection conn = (HttpURLConnection) url.openConnection();
            conn.setRequestMethod("POST");
            conn.setDoOutput(true);
            String json = String.format(
                    "{\"tagId\":\"%s\",\"timestamp\":\"%s\"}",
                    epc, formattedTime);

            byte[] body = json.getBytes(StandardCharsets.UTF_8);

            conn.setRequestProperty("Content-Type", "application/json; charset=UTF-8");
            conn.setRequestProperty("Content-Length", String.valueOf(body.length));
            conn.setFixedLengthStreamingMode(body.length);

            try (OutputStream os = conn.getOutputStream()) {
                os.write(body);
                os.flush();
                os.close();
            }
            int responseCode = conn.getResponseCode();
            InputStream is;
            if (responseCode >= 400) {
                is = conn.getErrorStream();
            } else {
                is = conn.getInputStream();
            }

            BufferedReader reader = new BufferedReader(new InputStreamReader(is, StandardCharsets.UTF_8));
            StringBuilder sb = new StringBuilder();
            String line;
            while ((line = reader.readLine()) != null) {
                sb.append(line).append(System.lineSeparator());
            }
            reader.close();
            //String responseBody = sb.toString();
            System.out.println(
                    "POST for tag " + epc + " responded with code: " + responseCode);//, body: " + responseBody);
            if (responseCode == 500) {
                logFailedTag(epc, formattedTime);
            }
            if (responseCode == 208 || responseCode == 404) {
                ignoredTags.add(epc);
                conn.disconnect();
                return;
            }
            conn.disconnect();
        } catch (Exception e) {
            System.out.println("Error sending tag data for " + epc + ": " + e.getMessage());
        }
    }

    /***
     * Uloží neúspěšně zpracovaný tag na konec textového souboru
     * 
     * @param epc       EPC daného tagu
     * @param timestamp Datum a čas, kdy byl tag přečten ve formátu "yyyy-MM-dd
     *                  HH:mm:ss.SSS"
     */
    private static void logFailedTag(String epc, String timestamp) {
        try (FileWriter writer = new FileWriter("failed_tags.txt", true)) {
            writer.write(epc + "," + timestamp + "\n");
            writer.flush();
            System.out.println("Logged failed tag " + epc + " at " + timestamp);
        } catch (IOException e) {
            System.out.println("Error logging failed tag: " + e.getMessage());
        }
    }

    public static void main(String[] args) {
        final ScheduledExecutorService scheduler = Executors.newScheduledThreadPool(10);
        final ConcurrentHashMap<String, PendingTag> pendingTags = new ConcurrentHashMap<>();

        try {
            String hostname = "169.254.1.1";
            ImpinjReader reader = new ImpinjReader();
            boolean readerConnected = false;

            try {
                System.out.println("Connecting to reader");
                reader.connect(hostname);
                readerConnected = true;
            } catch (OctaneSdkException ex) {
                System.out.println("Failed to connect to reader. " + ex.getMessage());
                return;
            }

            if (readerConnected) {
                Settings settings = reader.queryDefaultSettings();
                ReportConfig report = settings.getReport();

                report.setIncludeAntennaPortNumber(true);
                report.setMode(ReportMode.Individual);

                settings.setRfMode(1000);
                settings.setSearchMode(SearchMode.ReaderSelected);
                settings.setSession(3);

                AntennaConfigGroup antennas = settings.getAntennas();
                antennas.disableAll();
                antennas.enableById(new short[] { 1 });

                antennas.getAntenna((short) 1).setIsMaxRxSensitivity(true);
                antennas.getAntenna((short) 1).setIsMaxTxPower(true);

                reader.applySettings(settings);

                reader.setTagReportListener(new TagReportListener() {
                    @Override
                    public void onTagReported(ImpinjReader reader, TagReport reportData) {
                        List<Tag> tags = reportData.getTags();
                        for (Tag t : tags) {
                            final String epc = t.getEpc().toString();

                            if (ignoredTags.contains(epc)) {
                                continue;
                            }

                            if (!epc.contains("ABBA 2504")) {
                                continue;
                            }

                            if (!pendingTags.containsKey(epc)) {
                                final long firstTimestamp = System.currentTimeMillis();
                                ScheduledFuture<?> future = scheduler.schedule(() -> {
                                    processTag(epc, firstTimestamp);
                                    pendingTags.remove(epc);
                                }, 15, TimeUnit.SECONDS);
                                pendingTags.put(epc, new PendingTag(epc, firstTimestamp, future));
                            }
                        }
                    }
                });
            }

            System.out.println("Press 's' to start measurement:");
            Scanner s = new Scanner(System.in);
            while (true) {
                String input = s.nextLine();
                if (input.equalsIgnoreCase("s")) {
                    System.out.println("Measurement started");
                    if (readerConnected) {
                        reader.start();
                    }
                    break;
                } else {
                    System.out.println("Invalid input. Press 's' to start measurement:");
                }
            }

            while (true) {
                System.out.println("Press 'q' to quit measurement:");
                String cmd = s.nextLine();
                if (cmd.equalsIgnoreCase("q")) {
                    System.out.println("Stopping measurement");
                    if (readerConnected) {
                        reader.stop();
                    }
                    break;
                }
            }

            System.out.println("Disconnecting");
            if (readerConnected) {
                reader.disconnect();
            }

            // Shutdown the scheduler gracefully
            scheduler.shutdown();
            try {
                if (!scheduler.awaitTermination(5, TimeUnit.SECONDS)) {
                    scheduler.shutdownNow();
                }
            } catch (InterruptedException e) {
                scheduler.shutdownNow();
            }

        } catch (OctaneSdkException ex) {
            System.out.println("Octane SDK exception: " + ex.getMessage());
        } catch (Exception ex) {
            System.out.println("Exception: " + ex.getMessage());
            ex.printStackTrace(System.out);
        }
    }
}
