"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const http_1 = __importDefault(require("http"));
const crypto_1 = __importDefault(require("crypto"));
const express_1 = __importDefault(require("express"));
const cors_1 = __importDefault(require("cors"));
const socket_io_1 = require("socket.io");
const dotenv_1 = __importDefault(require("dotenv"));
dotenv_1.default.config();
const PORT = parseInt(process.env.PORT || "4000", 10);
const CORS_ORIGIN = process.env.CORS_ORIGIN || "*";
const SIGNING_KEY = process.env.GATEWAY_SIGNING_KEY || "";
if (!SIGNING_KEY) {
    // eslint-disable-next-line no-console
    console.warn("[gateway] GATEWAY_SIGNING_KEY missing. Token verification will fail.");
}
function b64urlToBuffer(input) {
    const pad = "=".repeat((4 - (input.length % 4)) % 4);
    const base64 = (input + pad).replace(/-/g, "+").replace(/_/g, "/");
    return Buffer.from(base64, "base64");
}
function safeJsonParse(s) {
    try {
        return JSON.parse(s);
    }
    catch {
        return null;
    }
}
function verifyRealtimeToken(token) {
    const parts = token.split(".");
    if (parts.length !== 3)
        return null;
    const [h, p, s] = parts;
    const header = safeJsonParse(b64urlToBuffer(h).toString("utf8"));
    if (!header || header.alg !== "HS256")
        return null;
    const payload = safeJsonParse(b64urlToBuffer(p).toString("utf8"));
    if (!payload)
        return null;
    const now = Math.floor(Date.now() / 1000);
    if (!payload.exp || payload.exp < now)
        return null;
    if (!payload.tenant_id || !payload.user_id)
        return null;
    const data = `${h}.${p}`;
    const expected = crypto_1.default.createHmac("sha256", SIGNING_KEY).update(data).digest();
    const actual = b64urlToBuffer(s);
    if (expected.length !== actual.length)
        return null;
    if (!crypto_1.default.timingSafeEqual(expected, actual))
        return null;
    return payload;
}
function threadRoom(tenantId, threadId) {
    // Tenant prefix zorunlu: cross-tenant dinleme riski olmaz.
    return `thread:${tenantId}:${threadId}`;
}
function tenantRoom(tenantId) {
    return `tenant:${tenantId}`;
}
function userRoom(tenantId, userId) {
    return `user:${tenantId}:${userId}`;
}
const app = (0, express_1.default)();
app.use((0, cors_1.default)({ origin: CORS_ORIGIN === "*" ? true : CORS_ORIGIN, credentials: true }));
app.use(express_1.default.json({ limit: "2mb" }));
app.get("/health", (_req, res) => {
    res.json({ ok: true, ts: Date.now() });
});
const server = http_1.default.createServer(app);
const io = new socket_io_1.Server(server, {
    cors: { origin: CORS_ORIGIN === "*" ? true : CORS_ORIGIN, credentials: true },
});
io.use((socket, next) => {
    const token = socket.handshake.auth?.token ||
        socket.handshake.headers["authorization"]?.replace(/^Bearer\s+/i, "");
    if (!token)
        return next(new Error("Missing token"));
    const payload = verifyRealtimeToken(token);
    if (!payload)
        return next(new Error("Invalid token"));
    socket.data.user = payload;
    next();
});
io.on("connection", (socket) => {
    const user = socket.data.user;
    socket.join(tenantRoom(user.tenant_id));
    socket.join(userRoom(user.tenant_id, user.user_id));
    socket.on("join_thread", (data) => {
        const threadId = Number(data?.thread_id);
        if (!Number.isFinite(threadId) || threadId <= 0)
            return;
        socket.join(threadRoom(user.tenant_id, threadId));
    });
    socket.on("leave_thread", (data) => {
        const threadId = Number(data?.thread_id);
        if (!Number.isFinite(threadId) || threadId <= 0)
            return;
        socket.leave(threadRoom(user.tenant_id, threadId));
    });
});
function verifyInternalSignature(rawBody, ts, signature) {
    if (!ts || !signature)
        return false;
    const data = `${ts}.${rawBody}`;
    const expected = crypto_1.default.createHmac("sha256", SIGNING_KEY).update(data).digest("hex");
    const a = Buffer.from(expected, "hex");
    const b = Buffer.from(signature, "hex");
    if (a.length !== b.length)
        return false;
    return crypto_1.default.timingSafeEqual(a, b);
}
// Raw body capture for signature check
app.post("/internal/broadcast", express_1.default.raw({ type: "application/json", limit: "2mb" }), (req, res) => {
    const ts = (req.header("x-marka-timestamp") || "").trim();
    const sig = (req.header("x-marka-signature") || "").trim();
    const rawBody = (req.body instanceof Buffer ? req.body.toString("utf8") : "") || "";
    if (!SIGNING_KEY)
        return res.status(500).json({ ok: false, error: "GATEWAY_SIGNING_KEY missing" });
    if (!verifyInternalSignature(rawBody, ts, sig)) {
        return res.status(401).json({ ok: false, error: "Invalid signature" });
    }
    const body = safeJsonParse(rawBody);
    if (!body || !body.tenant_id || !body.event) {
        return res.status(422).json({ ok: false, error: "Invalid payload" });
    }
    const tenantId = Number(body.tenant_id);
    const rooms = body.rooms || [];
    for (const r of rooms) {
        if (r.type === "tenant")
            io.to(tenantRoom(tenantId)).emit(body.event, body.payload);
        if (r.type === "thread")
            io.to(threadRoom(tenantId, Number(r.id))).emit(body.event, body.payload);
        if (r.type === "user")
            io.to(userRoom(tenantId, Number(r.id))).emit(body.event, body.payload);
    }
    return res.json({ ok: true });
});
server.listen(PORT, () => {
    // eslint-disable-next-line no-console
    console.log(`[gateway] listening on :${PORT}`);
});
