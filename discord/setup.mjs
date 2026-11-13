#!/usr/bin/env node
// Builds the Clyve.dev Discord server: roles, channels, permissions, artwork, welcome texts and webhooks.
// Safe to run again – existing roles and channels (matched by name) are updated, not duplicated.
//
//   DISCORD_TOKEN=… [GUILD_ID=…] [SITE_URL=https://clyve.dev] node discord/setup.mjs [--clean] [--repost]
//
//   --clean   delete Discord's default channels ("general", "Text Channels", …)
//   --repost  post the welcome/rules/info messages again even if the bot already posted them

import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { CATEGORIES, ONBOARDING, P, ROLES, WELCOME_SCREEN, messages } from './server.mjs';

const API = (process.env.DISCORD_API || 'https://discord.com/api/v10').replace(/\/$/, '');
const TOKEN = process.env.DISCORD_TOKEN || '';
const SITE = (process.env.SITE_URL || 'https://clyve.dev').replace(/\/$/, '');
const ARGS = new Set(process.argv.slice(2));
const ASSETS = join(dirname(fileURLToPath(import.meta.url)), 'assets');
const TYPES = { text: 0, voice: 2, category: 4, news: 5, forum: 15 };
const DEFAULT_NAMES = new Set(['general', 'allgemein', 'text channels', 'voice channels', 'textkanäle', 'sprachkanäle', 'lounge', 'gaming', 'hausaufgabenhilfe', 'sitzungsplanung', 'off-topic', 'treffpunkt']);

const log = (icon, msg) => console.log(`${icon} ${msg}`);
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const dataUri = (file) => 'data:image/png;base64,' + readFileSync(join(ASSETS, file)).toString('base64');

async function api(method, path, body, files) {
  for (let attempt = 0; attempt < 6; attempt++) {
    const init = { method, headers: { Authorization: `Bot ${TOKEN}`, 'User-Agent': 'clyve-setup (https://clyve.dev, 1.0)' } };
    if (files) {
      const form = new FormData();
      form.append('payload_json', JSON.stringify(body));
      files.forEach((f, i) => form.append(`files[${i}]`, new Blob([readFileSync(join(ASSETS, f))], { type: 'image/png' }), f));
      init.body = form;
    } else if (body !== undefined) {
      init.headers['Content-Type'] = 'application/json';
      init.body = JSON.stringify(body, (_, v) => (typeof v === 'bigint' ? v.toString() : v));
    }
    const res = await fetch(API + path, init);
    if (res.status === 429) {
      const wait = Number((await res.json().catch(() => ({}))).retry_after ?? 1);
      await sleep(Math.ceil(wait * 1000) + 250);
      continue;
    }
    if (res.status === 204) return null;
    const data = await res.json().catch(() => null);
    if (!res.ok) {
      const err = new Error(`${method} ${path} → ${res.status} ${data?.message ?? ''}${data?.errors ? ' ' + JSON.stringify(data.errors) : ''}`);
      err.status = res.status;
      throw err;
    }
    return data;
  }
  throw new Error(`${method} ${path}: rate limited too often`);
}

const soft = async (label, fn) => {
  try { return await fn(); } catch (e) { log('⚠️ ', `${label}: ${e.message}`); return null; }
};

async function main() {
  if (!TOKEN) {
    console.error('DISCORD_TOKEN fehlt. Siehe discord/README.md.');
    process.exit(1);
  }
  const me = await api('GET', '/users/@me');
  let guildId = process.env.GUILD_ID || '';
  if (!guildId) {
    const guilds = await api('GET', '/users/@me/guilds');
    if (guilds.length !== 1) {
      console.error(guilds.length ? `Der Bot ist auf ${guilds.length} Servern – bitte GUILD_ID angeben:\n` + guilds.map((g) => `  ${g.id}  ${g.name}`).join('\n')
        : `Der Bot ist noch auf keinem Server. Einladen:\nhttps://discord.com/oauth2/authorize?client_id=${me.id}&scope=bot&permissions=8`);
      process.exit(1);
    }
    guildId = guilds[0].id;
  }
  const guild = await api('GET', `/guilds/${guildId}`);
  log('🌸', `Richte „${guild.name}“ ein (als ${me.username}) …`);

  /* ---------- roles ---------- */
  const roleIds = {};
  let roles = await api('GET', `/guilds/${guildId}/roles`);
  for (const r of ROLES) {
    const body = { name: r.name, color: r.color, hoist: !!r.hoist, mentionable: !!r.mentionable, permissions: r.permissions };
    const existing = roles.find((x) => x.name === r.name && !x.managed);
    const role = existing
      ? await soft(`Rolle ${r.name}`, () => api('PATCH', `/guilds/${guildId}/roles/${existing.id}`, body)) ?? existing
      : await api('POST', `/guilds/${guildId}/roles`, body);
    roleIds[r.key] = role.id;
    log(existing ? '↻ ' : '＋', `Rolle ${r.name}`);
  }
  const member = await api('GET', `/guilds/${guildId}/members/${me.id}`);
  roles = await api('GET', `/guilds/${guildId}/roles`);
  const botTop = Math.max(0, ...roles.filter((r) => member.roles.includes(r.id)).map((r) => r.position));
  const positions = ROLES.map((r, i) => ({ id: roleIds[r.key], position: Math.max(1, botTop - 1 - i) }));
  await soft('Rollen sortieren (zieh die Bot-Rolle ganz nach oben und starte erneut)', () => api('PATCH', `/guilds/${guildId}/roles`, positions));
  const staff = [roleIds.team, roleIds.dev, roleIds.support];

  /* ---------- guild settings + community ---------- */
  await soft('Server-Icon', () => api('PATCH', `/guilds/${guildId}`, { icon: dataUri('icon.png') }));
  if (guild.features.includes('BANNER')) await soft('Banner', () => api('PATCH', `/guilds/${guildId}`, { banner: dataUri('banner.png') }));
  if (guild.features.includes('INVITE_SPLASH')) await soft('Einladungs-Hintergrund', () => api('PATCH', `/guilds/${guildId}`, { splash: dataUri('splash.png') }));
  await soft('Server-Einstellungen', () => api('PATCH', `/guilds/${guildId}`, { verification_level: 1, explicit_content_filter: 2, default_message_notifications: 1, preferred_locale: 'de' }));

  /* ---------- channels ---------- */
  let channels = await api('GET', `/guilds/${guildId}/channels`);
  if (ARGS.has('--clean')) {
    const ours = new Set(CATEGORIES.flatMap((c) => [c.name, ...c.channels.map((ch) => ch.name)]));
    for (const ch of channels.filter((c) => DEFAULT_NAMES.has(c.name.toLowerCase()) && !ours.has(c.name))) {
      await soft(`Löschen ${ch.name}`, () => api('DELETE', `/channels/${ch.id}`));
      log('－', `Standard-Kanal „${ch.name}“ entfernt`);
    }
    channels = await api('GET', `/guilds/${guildId}/channels`);
  }

  const overwrites = (access) => {
    const out = [];
    const add = (id, rule) => rule && out.push({ id, type: 0, allow: (rule.allow ?? 0n).toString(), deny: (rule.deny ?? 0n).toString() });
    add(guildId, access.everyone);
    if (access.customer) add(roleIds.customer, access.customer);
    if (access.staff) staff.forEach((id) => add(id, access.staff));
    add(roleIds.bots, { allow: P.VIEW | P.SEND | P.EMBED | P.ATTACH | P.HISTORY });
    out.push({ id: me.id, type: 1, allow: (P.VIEW | P.SEND | P.EMBED | P.ATTACH | P.HISTORY).toString(), deny: '0' });
    return out;
  };

  // Community unlocks announcement + forum channels; it needs a rules and an updates channel first.
  const find = (name, type) => channels.find((c) => c.name === name && (type === undefined || c.type === type));
  const ensure = async (spec, parentId, position, typeName) => {
    const type = TYPES[typeName];
    const body = { name: spec.name, type, parent_id: parentId, position, permission_overwrites: overwrites(spec.access ?? {}) };
    if (spec.topic && type !== TYPES.voice) body.topic = spec.topic;
    if (type === TYPES.forum) {
      body.available_tags = (spec.tags ?? []).map(([name, emoji]) => ({ name, emoji_name: emoji }));
      body.default_reaction_emoji = { emoji_name: '🌸' };
    }
    const existing = channels.find((c) => c.name === spec.name && c.type !== TYPES.category) ?? null;
    const { type: _, ...patch } = body;
    if (existing && (existing.type === type || (existing.type === TYPES.text && type === TYPES.news))) {
      if (existing.type !== type) patch.type = type; // text → announcement keeps the channel
      return [await api('PATCH', `/channels/${existing.id}`, patch), '↻ '];
    }
    if (existing) await api('DELETE', `/channels/${existing.id}`); // e.g. text → forum: recreate
    return [await api('POST', `/guilds/${guildId}/channels`, body), '＋'];
  };

  const ids = {};
  let community = guild.features.includes('COMMUNITY');
  for (const [ci, cat] of CATEGORIES.entries()) {
    const catBody = { name: cat.name, type: TYPES.category, position: ci };
    const existingCat = find(cat.name, TYPES.category);
    const category = existingCat ? (await api('PATCH', `/channels/${existingCat.id}`, { position: ci }), existingCat) : await api('POST', `/guilds/${guildId}/channels`, catBody);
    if (!existingCat) log('＋', `Kategorie ${cat.name}`);
    for (const [i, ch] of cat.channels.entries()) {
      const wanted = !community && (ch.type === 'news' || ch.type === 'forum') ? 'text' : ch.type;
      const [channel, mark] = await ensure(ch, category.id, i, wanted);
      ids[ch.key] = channel.id;
      log(mark, `${ch.name}${wanted !== ch.type ? ' (als Text, Community noch aus)' : ''}`);
    }
    channels = await api('GET', `/guilds/${guildId}/channels`);
    if (!community && ids.rules && ids.mod_log) {
      const ok = await soft('Community aktivieren', () => api('PATCH', `/guilds/${guildId}`, {
        features: [...new Set([...guild.features, 'COMMUNITY'])], rules_channel_id: ids.rules, public_updates_channel_id: ids.mod_log,
      }));
      if (ok) { community = true; log('✓ ', 'Community aktiviert (Ankündigungs- und Forenkanäle möglich)'); }
    }
  }
  // channels created as text before Community was on
  if (community) {
    for (const cat of CATEGORIES) for (const ch of cat.channels) {
      const cur = channels.find((c) => c.id === ids[ch.key]);
      if (cur && cur.type !== TYPES[ch.type]) {
        const [channel] = await ensure(ch, cur.parent_id, cur.position, ch.type);
        ids[ch.key] = channel.id;
        log('↻ ', `${ch.name} → ${ch.type === 'news' ? 'Ankündigungskanal' : 'Forum'}`);
      }
    }
  }
  await soft('System-Kanal', () => api('PATCH', `/guilds/${guildId}`, { system_channel_id: ids.chat, system_channel_flags: 0b1100 }));

  /* ---------- welcome screen + onboarding ---------- */
  if (community) {
    await soft('Willkommensbildschirm', () => api('PATCH', `/guilds/${guildId}/welcome-screen`, {
      enabled: true, description: WELCOME_SCREEN.description,
      welcome_channels: WELCOME_SCREEN.channels.map((c) => ({ channel_id: ids[c.key], description: c.description, emoji_name: c.emoji })),
    }));
    await soft('Onboarding (Ping-Rollen)', () => api('PUT', `/guilds/${guildId}/onboarding`, {
      enabled: true, mode: 0, default_channel_ids: ONBOARDING.defaults.map((k) => ids[k]).filter(Boolean),
      prompts: [{
        type: 0, title: ONBOARDING.prompt.title, single_select: false, required: false, in_onboarding: true,
        options: ONBOARDING.prompt.options.map((o) => ({ title: o.title, description: o.description, emoji_name: o.emoji, role_ids: [roleIds[o.role]], channel_ids: [] })),
      }],
    }));
  }

  /* ---------- messages ---------- */
  const fill = (v) => typeof v === 'string'
    ? v.replace(/<#(\w+)>/g, (m, k) => (ids[k] ? `<#${ids[k]}>` : m)).replace(/<@&(\w+)>/g, (m, k) => (roleIds[k] ? `<@&${roleIds[k]}>` : m))
    : Array.isArray(v) ? v.map(fill) : v && typeof v === 'object' ? Object.fromEntries(Object.entries(v).map(([k, x]) => [k, fill(x)])) : v;
  for (const [key, list] of Object.entries(messages(SITE))) {
    const channelId = ids[key];
    if (!channelId) continue;
    const recent = await api('GET', `/channels/${channelId}/messages?limit=20`);
    if (recent.some((m) => m.author?.id === me.id) && !ARGS.has('--repost')) { log('· ', `Nachrichten in ${key} schon vorhanden (--repost zum Neusenden)`); continue; }
    for (const msg of list) {
      const { image, ...payload } = fill(msg);
      payload.allowed_mentions = { parse: [] };
      await api('POST', `/channels/${channelId}/messages`, payload, image ? [image] : undefined);
    }
    log('✉️ ', `Nachrichten in ${key} gepostet`);
  }

  /* ---------- webhooks for the website ---------- */
  const hook = async (channelKey, name) => {
    const existing = (await api('GET', `/channels/${ids[channelKey]}/webhooks`)).find((w) => w.name === name && w.token);
    return existing ?? api('POST', `/channels/${ids[channelKey]}/webhooks`, { name, avatar: dataUri('icon.png') });
  };
  const siteHook = await soft('Webhook Website', () => hook('site_events', 'Clyve.dev'));
  const devlogHook = await soft('Webhook Devlog', () => hook('devlog', 'Clyve.dev Devlog'));
  const url = (w) => (w ? `https://discord.com/api/webhooks/${w.id}/${w.token}` : '(nicht erstellt)');

  console.log(`
✅ Fertig! Trag das im Staff-Bereich unter Verwaltung → Integrationen ein:

   Discord-Webhook (Benachrichtigungen):  ${url(siteHook)}
   Devlog-Kanal Webhook-URL:              ${url(devlogHook)}
   Devlog – Rolle erwähnen (Rollen-ID):   ${roleIds.ping_devlog}

   Die Webhook-URLs sind geheim – nicht öffentlich posten.
${community ? '' : '\n⚠️  Community ist aus: Ankündigungs- und Forenkanäle wurden als Textkanäle angelegt.\n   Aktiviere Community in den Servereinstellungen und starte das Skript erneut.\n'}
Weitere Schritte (nur im Discord-Client möglich):
   • Dir selbst die Rolle „${ROLES[0].name}“ geben, Kunden die Rolle „Kunde“
   • Den Bot danach wieder entfernen – er wird nur für das Setup gebraucht
`);
}

main().catch((e) => {
  console.error('❌ ' + e.message);
  if (e.status === 401) console.error('   Der Bot-Token stimmt nicht (Developer Portal → Bot → Reset Token).');
  if (e.status === 403) console.error('   Fehlende Rechte – lade den Bot mit Administrator-Recht ein und zieh seine Rolle nach oben.');
  process.exit(1);
});

