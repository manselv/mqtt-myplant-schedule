const express = require("express");
const bodyParser = require("body-parser");
const cron = require("node-cron");
const mqtt = require("mqtt");

const app = express();
app.use(bodyParser.json());

const MQTT_BROKER = "broker.emqx.io"; // ganti kalau kamu pakai broker sendiri
const MQTT_TOPIC = "sensor/data";

const client = mqtt.connect(MQTT_BROKER);

let tasks = []; // simpan semua cron tasks aktif

function clearTasks() {
  tasks.forEach((task) => task.stop());
  tasks = [];
}

// Fungsi untuk buat jadwal nyiram + auto stop setelah durasi
function scheduleWatering(times, duration) {
  clearTasks();

  times.forEach((timeStr) => {
    const [hour, minute] = timeStr.split(":");
    const cronTime = `${minute} ${hour} * * *`;

    const task = cron.schedule(cronTime, () => {
      console.log(`[${timeStr}] Start watering`);
      client.publish(MQTT_TOPIC, "startwatering");

      setTimeout(() => {
        console.log(`[${timeStr}] Stop watering after ${duration}s`);
        client.publish(MQTT_TOPIC, "stopwatering");
      }, duration * 1000);
    });

    tasks.push(task);
  });
}

// Endpoint untuk kirim jadwal + durasi dari Flutter
app.post("/schedule", (req, res) => {
  const { times, duration } = req.body;

  if (!Array.isArray(times) || typeof duration !== "number") {
    return res.status(400).json({ error: "Invalid input format" });
  }

  scheduleWatering(times, duration);
  res.json({ message: "Watering schedule updated", times, duration });
});

// Endpoint manual watering dari Flutter
app.post("/water-now", (req, res) => {
  const { duration } = req.body;
  if (typeof duration !== "number") {
    return res.status(400).json({ error: "Invalid duration" });
  }

  client.publish(MQTT_TOPIC, "start");
  console.log(`[Manual] Start watering`);

  setTimeout(() => {
    client.publish(MQTT_TOPIC, "stop");
    console.log(`[Manual] Stop watering after ${duration}s`);
  }, duration * 1000);

  res.json({ message: "Manual watering started", duration });
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
  console.log(`Server running on port ${PORT}`);
});
