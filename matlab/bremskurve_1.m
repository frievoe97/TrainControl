v_0 = 8.33;    % Startgeschwindigkeit
v_1 = 0;        % Zielgeschwindigkeit

t = 2;          % Bremskraftentwicklungszeit (Durschlagzeit im Zug, Aufbauzeit

a = 0.2;          % Bremsverzögerung

st = 1;         % Steigung

g = 9.81;       % Fallbeschleunigung

s_b = (v_0 - v_1) * t + ((v_0 * v_0 - v_1 * v_1)/(2 * ((g * st/1000) + a))); % Bremsweg

a_test = ((v_0 * v_0)/(364))+(9.81/1000);

disp(s_b);

disp(a_test);

% figure 1: x:v_0 [0:40]; y:v_1 [0:40]

x_1 = linspace(0, 40, 20);
y_1 = linspace(0, 40, 20);
[X_1,Y_1] = meshgrid(x_1, y_1);

Z_1 = (X_1 - Y_1) * t + ((X_1.^2 - Y_1.^2)/(2 * ((g * st/1000) + a)));
contourf(X_1,Y_1,Z_1,15)



f = @(x_1,y_1) (x_1 - y_1) * t + ((x_1.^2 - y_1.^2)/(2 * ((g * st/1000) + a)));
%fcontour(f)




