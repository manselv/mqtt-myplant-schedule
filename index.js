const express = require("express");
const bodyParser = require("body-parser");
const cron = require("node-cron");
const mqtt = require("mqtt");

const app = express();
app.use(bodyParser.json());

// const MQTT_BROKER = "mqtt://broker.emqx.io";
const MQTT_BROKER = "wss://mqtt.myplant.site/mqtt";
const MQTT_TOPIC = "sensor/data";

const client = mqtt.connect(MQTT_BROKER, {
  clientId: `client_${Math.random().toString(16).slice(2)}`,
  reconnectPeriod: 1000,
});

let tasks = [];

function clearTasks() {
  tasks.forEach((task) => task.stop());
  tasks = [];
}

function scheduleWatering(schedules) {
  clearTasks();

  schedules.forEach(({ time, duration }) => {
    const [hour, minute] = time.split(":");
    const cronTime = `${minute} ${hour} * * *`;

    const task = cron.schedule(cronTime, () => {
      console.log(`[${time}] Start watering`);
      client.publish(MQTT_TOPIC, "startwatering");

      setTimeout(() => {
        console.log(`[${time}] Stop watering after ${duration}s`);
        client.publish(MQTT_TOPIC, "stopwatering");
      }, duration * 1000);
    });

    tasks.push(task);
  });
}

app.post("/schedule", (req, res) => {
  const schedules = req.body;

  if (!Array.isArray(schedules)) {
    return res.status(400).json({ error: "Expected array of schedules" });
  }

  for (const item of schedules) {
    if (
      typeof item.time !== "string" ||
      !item.time.match(/^\d{2}:\d{2}$/) ||
      typeof item.duration !== "number"
    ) {
      return res.status(400).json({ error: "Invalid schedule item format" });
    }
  }

  scheduleWatering(schedules);
  console.log("Watering schedules updated:", schedules);
  res.json({ message: "Watering schedule updated", schedules });
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
  console.log(`Server running on port ${PORT}`);
});
