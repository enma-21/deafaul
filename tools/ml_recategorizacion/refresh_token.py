#!/usr/bin/env python3
"""
Renueva un access_token de Mercado Libre a partir de un refresh_token.

Los access tokens de Mercado Libre expiran a las 6 horas. Este helper
hace el intercambio de OAuth para obtener uno nuevo sin tener que
re-autorizar la app manualmente cada vez.

Uso:

    python refresh_token.py \
        --client-id "$ML_CLIENT_ID" \
        --client-secret "$ML_CLIENT_SECRET" \
        --refresh-token "$ML_REFRESH_TOKEN"

Imprime el nuevo access_token y refresh_token (Mercado Libre rota el
refresh_token en cada uso: hay que guardar el nuevo para la próxima vez).
"""

from __future__ import annotations

import argparse
import json
import os
import sys

import requests

API_BASE = "https://api.mercadolibre.com"


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__, formatter_class=argparse.RawDescriptionHelpFormatter)
    parser.add_argument("--client-id", default=os.environ.get("ML_CLIENT_ID"))
    parser.add_argument("--client-secret", default=os.environ.get("ML_CLIENT_SECRET"))
    parser.add_argument("--refresh-token", default=os.environ.get("ML_REFRESH_TOKEN"))
    args = parser.parse_args()

    missing = [name for name, val in [
        ("--client-id", args.client_id),
        ("--client-secret", args.client_secret),
        ("--refresh-token", args.refresh_token),
    ] if not val]
    if missing:
        raise SystemExit(f"Faltan argumentos (o variables de entorno equivalentes): {', '.join(missing)}")

    resp = requests.post(
        f"{API_BASE}/oauth/token",
        data={
            "grant_type": "refresh_token",
            "client_id": args.client_id,
            "client_secret": args.client_secret,
            "refresh_token": args.refresh_token,
        },
        headers={"Content-Type": "application/x-www-form-urlencoded"},
        timeout=30,
    )

    if resp.status_code != 200:
        print(f"Error {resp.status_code}: {resp.text}", file=sys.stderr)
        return 1

    data = resp.json()
    print(json.dumps(data, indent=2))
    print("\nExporta el nuevo access_token para usarlo con recategorize_listings.py:")
    print(f"  export ML_ACCESS_TOKEN='{data.get('access_token')}'")
    print("\nGuarda también el nuevo refresh_token (Mercado Libre invalida el anterior):")
    print(f"  export ML_REFRESH_TOKEN='{data.get('refresh_token')}'")
    return 0


if __name__ == "__main__":
    sys.exit(main())
