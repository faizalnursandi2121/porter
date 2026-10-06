# ADR-019 — Mapping, Geolocation, and Geofence Strategy for MVP

**Status:** Accepted  
**Date:** 2026-10-01  
**Decision scope:** CMX Sekolah Rakyat EOS Operations Platform MVP

## Context
Site setup and attendance require map visualization, coordinate selection, and auditable geofence validation.

## Decision
Use Leaflet + react-leaflet for marker, draggable coordinate pin, and circle radius. Use the Browser Geolocation API through an internal adapter. Go Haversine backend calculation is the authority; PostGIS is not used in MVP. Public OSM tiles are limited to development/pilot; a production tile provider must be selected before go-live. The map is input/visualization only, not attendance authority.

## Consequences
Manual coordinate input remains fallback. Geocoding is out of scope MVP.

## References
ADR-010; architecture; PRD; UX; API; test strategy.
