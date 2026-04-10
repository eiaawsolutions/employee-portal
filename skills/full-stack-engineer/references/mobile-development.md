# Mobile Development Patterns

Platform-specific and cross-platform patterns for mobile application development. Apply when the project includes a mobile client.

---

## Cross-Platform vs Native Decision Tree

```
What's the priority?
├── Maximum performance / deep OS integration / AR/camera-heavy
│   └── Native (Swift/Kotlin)
├── Shared codebase with web app / existing React team
│   └── React Native
├── Shared codebase / rapid prototyping / custom UI-heavy
│   └── Flutter
├── Existing .NET team / enterprise ecosystem
│   └── .NET MAUI
└── Simple content app / web wrapper
    └── Capacitor / PWA
```

---

## React Native

### Project Structure
```
src/
├── app/                       # Navigation, root layout
├── components/                # Shared UI components
│   ├── ui/                    # Primitives (Button, Input, Card)
│   └── features/              # Feature-specific components
├── screens/                   # Screen components (one per route)
├── navigation/                # Navigation config (React Navigation / Expo Router)
├── services/                  # API clients, external service wrappers
├── stores/                    # State management (Zustand/Redux/Jotai)
├── hooks/                     # Custom React hooks
├── utils/                     # Pure utility functions
├── types/                     # TypeScript type definitions
├── constants/                 # App-wide constants, config
└── assets/                    # Images, fonts, animations
```

### Key Patterns
- **Expo preferred**: Use Expo SDK for managed workflow unless native module access demands bare workflow.
- **Navigation**: React Navigation (stack, tab, drawer) or Expo Router (file-based routing).
- **State management**: Zustand for simplicity, Redux Toolkit for complex state, React Query/TanStack Query for server state.
- **API layer**: Use React Query for caching, deduplication, and background refetching. Define API client with Axios or `fetch`.
- **Platform-specific code**: Use `Platform.select()` or `.ios.tsx`/`.android.tsx` file extensions.
- **Performance**: Use `FlatList` (not `ScrollView`) for lists. Memoize with `React.memo`, `useMemo`, `useCallback` where profiling shows need.
- **Animations**: Use `react-native-reanimated` for performant 60fps animations on the UI thread.
- **Testing**: Jest + React Native Testing Library for component tests. Detox or Maestro for E2E.

### Security
- No secrets in JavaScript bundle (use env vars via EAS Secrets / `react-native-config`).
- Use `react-native-keychain` or `expo-secure-store` for tokens/credentials.
- Certificate pinning for sensitive APIs (`react-native-ssl-pinning`).
- Enable ProGuard (Android) and bitcode (iOS) for release builds.
- Disable JavaScript debugging in release builds.
- Validate deep links — don't trust URI parameters for navigation state.

---

## Flutter

### Project Structure
```
lib/
├── main.dart                  # Entry point, app config
├── app/
│   ├── routes.dart            # Route definitions
│   └── theme.dart             # ThemeData, colors, typography
├── features/
│   └── auth/
│       ├── data/              # Repositories, data sources, DTOs
│       ├── domain/            # Entities, use cases, repository interfaces
│       └── presentation/      # Screens, widgets, state (BLoC/Riverpod)
├── core/
│   ├── network/               # Dio client, interceptors
│   ├── storage/               # Local storage (Hive, SharedPreferences)
│   ├── constants/
│   └── utils/
└── shared/
    └── widgets/               # Reusable UI components
```

### Key Patterns
- **State management**: Riverpod (recommended), BLoC, or Provider. Pick one — don't mix.
- **Architecture**: Clean Architecture or feature-first. Separate data → domain → presentation.
- **Navigation**: GoRouter for declarative routing with deep link support.
- **API client**: Dio with interceptors for auth tokens, logging, retry.
- **Code generation**: Use `freezed` for immutable data classes, `json_serializable` for JSON mapping, `auto_route` for typed routes.
- **Platform channels**: For native code access. Keep channel calls in a service layer, not in widgets.
- **Testing**: Unit tests for business logic, widget tests for UI, integration tests for flows. `mocktail` for mocking.
- **Performance**: Use `const` constructors. Profile with Flutter DevTools. Use `RepaintBoundary` for complex widgets.

### Security
- Use `flutter_secure_storage` for tokens and credentials (backed by Keychain/Keystore).
- Enable code obfuscation: `flutter build apk --obfuscate --split-debug-info=...`.
- Certificate pinning via Dio interceptor or `http_certificate_pinning`.
- No sensitive data in `SharedPreferences` (unencrypted).
- Validate all deep link parameters.
- Use `--release` mode — debug mode exposes Dart VM service.

---

## Swift (iOS)

### Project Structure
```
App/
├── App.swift                  # @main entry, WindowGroup
├── Features/
│   └── Auth/
│       ├── Views/             # SwiftUI Views
│       ├── ViewModels/        # ObservableObject classes
│       ├── Models/            # Data models (Codable structs)
│       └── Services/          # API client, business logic
├── Core/
│   ├── Network/               # URLSession wrapper, API client
│   ├── Storage/               # CoreData / SwiftData / UserDefaults
│   ├── Extensions/            # Swift extensions
│   └── Constants/
├── SharedUI/                  # Reusable SwiftUI components
└── Resources/                 # Assets, Localizable.strings
```

### Key Patterns
- **SwiftUI-first**: Use SwiftUI for new projects. UIKit for performance-critical views or when SwiftUI lacks the API.
- **Architecture**: MVVM with `@Observable` (iOS 17+) or `ObservableObject` + `@Published`.
- **Concurrency**: Use Swift Concurrency (`async/await`, `Task`, `Actor`) — not completion handlers.
- **Networking**: `URLSession` with async/await. Use `Codable` for JSON serialization.
- **Persistence**: SwiftData (iOS 17+) or Core Data. UserDefaults for simple preferences only.
- **Dependency injection**: Use protocol-oriented injection or a lightweight DI container (Factory, Swinject).
- **Testing**: XCTest for unit/integration. XCUITest for UI tests. Use protocols for mockability.
- **Error handling**: Use typed `Error` enums. Map network errors to domain errors at the service boundary.

### Security
- Keychain Services for secrets (via `KeychainAccess` library or raw Security framework).
- App Transport Security (ATS) enabled — HTTPS only.
- Certificate pinning via `URLSessionDelegate` or TrustKit.
- Jailbreak detection for sensitive apps (not foolproof, but raises the bar).
- Biometric auth via LocalAuthentication framework for sensitive operations.
- Data protection API (`FileProtectionType.complete`) for files at rest.
- No sensitive data in `UserDefaults` (unencrypted plist).

---

## Kotlin (Android)

### Project Structure
```
app/src/main/java/com/example/app/
├── di/                        # Hilt modules
├── data/
│   ├── remote/                # Retrofit API interfaces, DTOs
│   ├── local/                 # Room DAOs, entities
│   └── repository/            # Repository implementations
├── domain/
│   ├── model/                 # Domain entities
│   ├── repository/            # Repository interfaces
│   └── usecase/               # Use cases
├── presentation/
│   ├── screens/               # Composables (screens)
│   ├── components/            # Reusable Composables
│   ├── viewmodel/             # ViewModels
│   └── navigation/            # NavHost, routes
└── core/
    ├── network/               # OkHttp interceptors, NetworkModule
    └── utils/
```

### Key Patterns
- **Jetpack Compose**: Default for new UI. XML layouts for legacy or complex custom views.
- **Architecture**: MVVM + Clean Architecture. Use `ViewModel` + `StateFlow`/`SharedFlow`.
- **Dependency injection**: Hilt (recommended) or Koin.
- **Networking**: Retrofit + OkHttp + Moshi/kotlinx.serialization.
- **Local storage**: Room for structured data. DataStore for preferences (replaces SharedPreferences).
- **Coroutines**: Use structured concurrency. `viewModelScope` for ViewModel coroutines. Never use `GlobalScope`.
- **Navigation**: Jetpack Navigation Compose with type-safe args.
- **Testing**: JUnit + MockK for unit tests. Espresso or Compose testing for UI. Robolectric for tests needing Android framework.

### Security
- EncryptedSharedPreferences or AndroidKeystore for tokens and credentials.
- Network security config for certificate pinning (`res/xml/network_security_config.xml`).
- ProGuard/R8 for code shrinking and obfuscation in release builds.
- SafetyNet / Play Integrity API for device attestation.
- BiometricPrompt for sensitive operation confirmation.
- No sensitive data in logs (`Log.d` stripped in release via ProGuard rules).
- Content Providers: Set `exported=false` unless intentionally sharing data.
- Intent validation — verify content of received Intents.

---

## Mobile-Specific API Design

When the backend serves mobile clients, consider:

| Concern | Pattern |
|---|---|
| Bandwidth | Sparse fieldsets / GraphQL — let clients request only needed fields |
| Offline support | ETags / `If-Modified-Since` for caching. Delta sync endpoints. |
| Pagination | Cursor-based pagination (not offset-based) for stable results |
| Push notifications | FCM (Android), APNs (iOS). Backend sends to topic or device token. |
| Auth tokens | Short-lived access tokens + long-lived refresh tokens stored securely |
| Versioning | API versioning via URL (`/v1/`) or header. Support N-1 version minimum. |
| File uploads | Presigned URLs to object storage — don't proxy through API server |
| Error format | Consistent error envelope: `{ "code": "...", "message": "...", "details": [...] }` |

## App Store Deployment Checklist

- [ ] App signing configured (Android: upload key + Play App Signing; iOS: distribution certificate + provisioning profile)
- [ ] Version number and build number incremented
- [ ] Release build tested on physical devices (not just emulators)
- [ ] ProGuard/R8 enabled and tested (Android)
- [ ] App size optimized (remove unused assets, split APKs / app bundles)
- [ ] Privacy policy URL provided
- [ ] Required permissions declared with usage descriptions
- [ ] Deep links / universal links configured and tested
- [ ] Crash reporting configured (Firebase Crashlytics, Sentry)
- [ ] Analytics configured (opt-in per privacy requirements)
- [ ] Screenshots and store listing prepared
- [ ] Push notification certificates/keys configured
