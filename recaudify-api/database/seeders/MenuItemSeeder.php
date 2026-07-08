<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class MenuItemSeeder extends Seeder
{
    public function run(): void
    {
        $authParentIds = MenuItem::where("label", "Auth")->pluck("id");
        MenuItem::whereIn("parent_id", $authParentIds)->where("label", "Sesiones")->delete();

        $groups = [
            [
                "label" => "Cobranza",
                "items" => [
                    [
                        "label" => "Clientes",
                        "icons" => ["M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"],
                        "route" => "core/customers",
                        "permission" => null,
                    ],
                    [
                        "label" => "Pedidos",
                        "icons" => [
                            "M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2",
                        ],
                        "route" => "core/orders",
                        "permission" => null,
                    ],
                ],
            ],
            [
                "label" => "Tablas Genéricas",
                "items" => [
                    [
                        "label" => "Auth",
                        "icons" => [
                            "M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z",
                        ],
                        "route" => null,
                        "permission" => null,
                        "children" => [
                            [
                                "label" => "Usuarios",
                                "icons" => [
                                    "M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z",
                                ],
                                "route" => "/admin/users",
                                "permission" => "users.view",
                            ],
                            [
                                "label" => "Roles",
                                "icons" => [
                                    "M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z",
                                ],
                                "route" => "/admin/roles",
                                "permission" => "roles.view",
                            ],
                            [
                                "label" => "Permisos",
                                "icons" => [
                                    "M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z",
                                ],
                                "route" => "/admin/permissions",
                                "permission" => "permissions.view",
                            ],
                            [
                                "label" => "Horarios",
                                "icons" => ["M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"],
                                "route" => "/admin/schedules",
                                "permission" => "schedules.view",
                            ],
                        ],
                    ],
                    [
                        "label" => "Negocio",
                        "icons" => [
                            "M3 21h18M5 21V7l8-4v18M19 21V11l-6-4m-2 4h.01M9 8h.01M9 12h.01M9 16h.01M5 12h.01M5 16h.01",
                        ],
                        "route" => null,
                        "permission" => null,
                        "children" => [
                            [
                                "label" => "Productos",
                                "icons" => ["M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"],
                                "route" => "/admin/products",
                                "permission" => "catalogs.view",
                            ],
                            [
                                "label" => "Tarifas",
                                "icons" => [
                                    "M7 7h.01M7 3h5a1.99 1.99 0 011.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.99 1.99 0 013 12V7a4 4 0 014-4z",
                                ],
                                "route" => "/admin/rates",
                                "permission" => "catalogs.view",
                            ],
                            [
                                "label" => "Vendedores",
                                "icons" => ["M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"],
                                "route" => "/admin/sellers",
                                "permission" => "catalogs.view",
                            ],
                            [
                                "label" => "Motivos de llamada",
                                "icons" => [
                                    "M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z",
                                ],
                                "route" => "/admin/call-reasons",
                                "permission" => "catalogs.view",
                            ],
                            [
                                "label" => "Cobradores",
                                "icons" => [
                                    "M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z",
                                ],
                                "route" => null,
                                "permission" => null,
                            ],
                        ],
                    ],
                ],
            ],
            [
                "label" => "Configuraciones",
                "items" => [
                    [
                        "label" => "Parámetros",
                        "icons" => [
                            "M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z",
                            "M15 12a3 3 0 11-6 0 3 3 0 016 0z",
                        ],
                        "route" => "/admin/parameters",
                        "permission" => "parameters.view",
                    ],
                    [
                        "label" => "Mis sesiones",
                        "icons" => ["M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"],
                        "route" => "/my-sessions",
                        "permission" => null,
                    ],
                    [
                        "label" => "Sesiones",
                        "icons" => [
                            "M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z",
                        ],
                        "route" => "/admin/sessions",
                        "permission" => "sessions.view",
                    ],
                    [
                        "label" => "Menús",
                        "icons" => ["M4 6h16M4 12h16M4 18h16"],
                        "route" => "/admin/menu-items",
                        "permission" => "menu.view",
                    ],
                    [
                        "label" => "Actividades",
                        "icons" => [
                            "M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4",
                        ],
                        "route" => "/admin/activity",
                        "permission" => "audit.view",
                    ],
                    [
                        "label" => "Accesos",
                        "icons" => ["M5 13l4 4L19 7M9 12a4 4 0 100-8 4 4 0 000 8zm0 0c-2.21 0-4 1.79-4 4v1h6"],
                        "route" => "/admin/access-log",
                        "permission" => "access.view",
                    ],
                ],
            ],
        ];

        foreach ($groups as $groupOrder => $group) {
            $groupItem = MenuItem::updateOrCreate(
                ["label" => $group["label"], "parent_id" => null],
                ["order" => $groupOrder, "is_active" => true],
            );

            $this->syncChildren($group["items"], $groupItem->id);
        }
    }

    private function syncChildren(array $items, int $parentId): void
    {
        foreach ($items as $order => $item) {
            $menuItem = MenuItem::updateOrCreate(
                ["label" => $item["label"], "parent_id" => $parentId],
                [
                    "icons" => $item["icons"],
                    "route" => $item["route"],
                    "permission" => $item["permission"],
                    "order" => $order,
                    "is_active" => true,
                ],
            );

            if (!empty($item["children"])) {
                $this->syncChildren($item["children"], $menuItem->id);
            }
        }
    }
}
