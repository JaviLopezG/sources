FROM node:20-alpine

WORKDIR /app

# Copy package.json first for caching
COPY package*.json ./

# Install production dependencies only
RUN npm install --only=production

COPY . .

EXPOSE 3000

CMD ["node", "server.js"]
