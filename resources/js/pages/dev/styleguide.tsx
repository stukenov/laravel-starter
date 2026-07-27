import { Head } from '@inertiajs/react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Skeleton } from '@/components/ui/skeleton';

/**
 * Живой стайлгайд. Доступен только при APP_ENV=local.
 *
 * Это не демо-контент, а рабочий инструмент: единственное место, где видно
 * все компоненты разом под текущими токенами. Правка токенов проверяется
 * здесь за один взгляд, а не обходом десяти экранов.
 */

function Section({
    title,
    hint,
    children,
}: {
    title: string;
    hint?: string;
    children: React.ReactNode;
}) {
    return (
        <section className="space-y-3">
            <div>
                <h2 className="text-[15px] font-semibold">{title}</h2>
                {hint && (
                    <p className="text-xs text-muted-foreground">{hint}</p>
                )}
            </div>
            <div className="rounded-md border bg-card p-4">{children}</div>
        </section>
    );
}

const USERS = [
    {
        name: 'Сакен Тукенов',
        email: 'saken@example.kz',
        role: 'админ',
        date: '27 июл',
    },
    {
        name: 'Айгүл Иманова',
        email: 'aigul@example.kz',
        role: 'автор',
        date: '26 июл',
    },
    {
        name: 'Дәурен Құрманов',
        email: 'dauren@example.kz',
        role: 'автор',
        date: '24 июл',
    },
];

export default function Styleguide() {
    return (
        <>
            <Head title="Стайлгайд" />

            <div className="density-panel mx-auto max-w-4xl space-y-8 p-8">
                <header>
                    <h1 className="text-xl font-semibold tracking-tight">
                        Стайлгайд
                    </h1>
                    <p className="text-muted-foreground">
                        Плотный язык системных настроек macOS. Волоски вместо
                        теней. Акцент — системный синий.
                    </p>
                </header>

                <Section
                    title="Типографика"
                    hint="Панель 13px, публичные страницы 17px. Оба масштаба на Inter."
                >
                    <div className="space-y-2">
                        <p className="text-xl font-semibold tracking-tight">
                            Заголовок страницы 20px
                        </p>
                        <p className="text-[15px] font-semibold">
                            Заголовок раздела 15px
                        </p>
                        <p>Основной текст 13px — плотность панели</p>
                        <p className="text-muted-foreground">
                            Второстепенный текст того же размера
                        </p>
                        <p className="text-xs tracking-wide text-muted-foreground uppercase">
                            Метка 11px
                        </p>
                        <Separator className="my-3" />
                        <p className="text-[17px] leading-relaxed">
                            Публичный текст 17px — воздух лендинга. Проверка
                            казахских букв: әңгіме, ғылым, құрылыс, өнер, үлгі,
                            һәм, іс.
                        </p>
                    </div>
                </Section>

                <Section title="Кнопки" hint="Скругление 7px, высота 28px">
                    <div className="flex flex-wrap items-center gap-2">
                        <Button size="sm">Сохранить</Button>
                        <Button size="sm" variant="secondary">
                            Отмена
                        </Button>
                        <Button size="sm" variant="outline">
                            Экспорт
                        </Button>
                        <Button size="sm" variant="ghost">
                            Сбросить
                        </Button>
                        <Button size="sm" variant="destructive">
                            Удалить
                        </Button>
                        <Button size="sm" variant="link">
                            Подробнее
                        </Button>
                        <Button size="sm" disabled>
                            Недоступно
                        </Button>
                    </div>
                </Section>

                <Section title="Поля" hint="Кольцо фокуса акцентного цвета">
                    <div className="grid max-w-sm gap-3">
                        <div className="grid gap-1.5">
                            <Label htmlFor="sg-name">Имя</Label>
                            <Input id="sg-name" defaultValue="Сакен Тукенов" />
                        </div>
                        <div className="grid gap-1.5">
                            <Label htmlFor="sg-role">Роль</Label>
                            <Select defaultValue="admin">
                                <SelectTrigger id="sg-role">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="admin">Админ</SelectItem>
                                    <SelectItem value="author">
                                        Автор
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="flex items-center gap-2">
                            <Checkbox id="sg-check" defaultChecked />
                            <Label htmlFor="sg-check">Подтверждён</Label>
                        </div>
                    </div>
                </Section>

                <Section title="Метки и аватары">
                    <div className="flex flex-wrap items-center gap-2">
                        <Badge>админ</Badge>
                        <Badge variant="secondary">автор</Badge>
                        <Badge variant="outline">гость</Badge>
                        <Badge variant="destructive">заблокирован</Badge>
                        <Avatar className="size-7">
                            <AvatarFallback className="bg-primary text-[10px] text-primary-foreground">
                                СТ
                            </AvatarFallback>
                        </Avatar>
                        <Avatar className="size-7">
                            <AvatarFallback className="text-[10px]">
                                АИ
                            </AvatarFallback>
                        </Avatar>
                    </div>
                </Section>

                <Section
                    title="Список"
                    hint="Главная проверка плотности: строки разделены волоском"
                >
                    <div className="overflow-hidden rounded-md border">
                        <div className="flex bg-muted/50 px-3 py-1.5 text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">
                            <span className="flex-[2]">Имя</span>
                            <span className="flex-[2]">Почта</span>
                            <span className="w-24">Роль</span>
                            <span className="w-20 text-right">Создан</span>
                        </div>
                        {USERS.map((user) => (
                            <div
                                key={user.email}
                                className="flex items-center border-t px-3 py-2"
                            >
                                <span className="flex flex-[2] items-center gap-2">
                                    <Avatar className="size-5">
                                        <AvatarFallback className="bg-primary text-[9px] text-primary-foreground">
                                            {user.name
                                                .split(' ')
                                                .map((part) => part[0])
                                                .join('')}
                                        </AvatarFallback>
                                    </Avatar>
                                    {user.name}
                                </span>
                                <span className="flex-[2] text-muted-foreground">
                                    {user.email}
                                </span>
                                <span className="w-24">
                                    <Badge variant="outline">{user.role}</Badge>
                                </span>
                                <span className="w-20 text-right text-muted-foreground">
                                    {user.date}
                                </span>
                            </div>
                        ))}
                    </div>
                </Section>

                <Section title="Карточка и сообщения">
                    <div className="space-y-3">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-[15px]">
                                    Заголовок карточки
                                </CardTitle>
                                <CardDescription>
                                    Пояснение под заголовком
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="text-muted-foreground">
                                Содержимое карточки. Граница волоском, тени нет.
                            </CardContent>
                        </Card>

                        <Alert>
                            <AlertTitle>Обычное сообщение</AlertTitle>
                            <AlertDescription>
                                Нейтральный тон, без цвета.
                            </AlertDescription>
                        </Alert>

                        <Alert variant="destructive">
                            <AlertTitle>Ошибка</AlertTitle>
                            <AlertDescription>
                                Цвет остаётся только для смысла.
                            </AlertDescription>
                        </Alert>
                    </div>
                </Section>

                <Section title="Загрузка">
                    <div className="max-w-sm space-y-2">
                        <Skeleton className="h-4 w-3/4" />
                        <Skeleton className="h-4 w-1/2" />
                        <Skeleton className="h-4 w-2/3" />
                    </div>
                </Section>
            </div>
        </>
    );
}
