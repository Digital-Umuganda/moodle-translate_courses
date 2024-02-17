**Introduction**
================
This project is part of Mbaza health system. This is an AI-powered chatbot for mobile devices. The chatbot helps its users to get information on **Malaria**, **Pneumonia**, and **Malnutrition**.

**How it works**
================
This side of the project which is the app that enables a user to chat with a chatbot. The chatbot takes the input sentences from the user in **Kinyarwanda** and translates it to **English**. The chatbot then uses the **GPT-4** model to get response. The GPT-4 response is then translated back to Kinyarwanda. And then the chatbot returns the response to the user in Kinyarwanda. This app is now to be used by the professional health workers. All the chats are stored in the database and evaluated by using the [``mbaza-health-web``](https://github.com/Digital-Umuganda/mbaza-health-web) website portal.

**Installation**
================
  - Clone this repository
  - Install dependencies
  ```shell 
  npm install
  ```
  - Run it on **Android** by using the command
  ```shell
  npm run android
  ```

## Requirements
### Development Environment
- Node.js
- Install Expo Go into your **Android** device.
### Production Environment
Follow the instructions in the Expo documentation at https://docs.expo.dev/build/setup/

**License**
===========
